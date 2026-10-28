#!/usr/bin/env python3
"""Bangun database/seed-data/{catalog,sales}.json dari 'Detail penjualan ide segar.xlsx'.

Kolom sheet: No. Invoice | Tanggal (serial Excel) | Pelanggan | Status Bayar |
Unit Usaha | Kategori | Produk | Ukuran | Finishing / Opsi | Satuan | Qty |
Harga Satuan (Rp) | Biaya Tambahan (Rp) | Harga per Item (Rp) | Total (Rp)

Format keluaran disamakan dengan yang lama agar DatabaseSeeder tidak berubah.
"""
import json
import zipfile
import xml.etree.ElementTree as ET
from datetime import datetime, timedelta
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
XLSX = ROOT / "Detail penjualan ide segar.xlsx"
OUT = ROOT / "database" / "seed-data"

NS = {"m": "http://schemas.openxmlformats.org/spreadsheetml/2006/main"}


def col_index(ref: str) -> int:
    """'A'->0, 'O'->14"""
    letters = "".join(c for c in ref if c.isalpha())
    idx = 0
    for ch in letters:
        idx = idx * 26 + (ord(ch) - ord("A") + 1)
    return idx - 1


def serial_to_date(value) -> str:
    """Serial Excel (1900 system) -> 'Y-m-d'. Handle float artifacts."""
    v = float(value)
    if 40000 <= v <= 60000:
        base = datetime(1899, 12, 30)
        return (base + timedelta(days=v)).strftime("%Y-%m-%d")
    return str(value)


def cell_value(cell, shared):
    t = cell.get("t")
    v = cell.find("m:v", NS)
    if t == "s" and v is not None:
        return shared[int(v.text)]
    if t == "inlineStr":
        is_ = cell.find("m:is", NS)
        if is_ is not None:
            txt = is_.find("m:t", NS)
            return txt.text if txt is not None and txt.text is not None else ""
    if v is not None and v.text is not None:
        raw = v.text.strip()
        if t == "b":
            return raw == "1"
        # angka utuh -> int bila memungkinkan
        try:
            f = float(raw)
            return int(f) if f.is_integer() else f
        except ValueError:
            return raw
    return ""


def main():
    with zipfile.ZipFile(XLSX) as zf:
        shared = []
        if "xl/sharedStrings.xml" in zf.namelist():
            sroot = ET.fromstring(zf.read("xl/sharedStrings.xml"))
            for si in sroot.findall("m:si", NS):
                text = "".join(
                    (t.text or "") for t in si.findall(".//m:t", NS)
                )
                shared.append(text)
        sroot = ET.fromstring(zf.read("xl/worksheets/sheet1.xml"))

    rows = []
    for row in sroot.findall(".//m:sheetData/m:row", NS):
        cells = {}
        for cell in row.findall("m:c", NS):
            cells[col_index(cell.get("r", "A"))] = cell_value(cell, shared)
        rows.append(cells)

    header = None
    data = []
    for cells in rows:
        if not cells:
            continue
        if header is None:
            header = [cells.get(i, "").strip().lower().replace(" ", "_") for i in sorted(cells)]
            continue
        # baris TOTAL / kosong
        invoice = str(cells.get(0, "")).strip()
        if not invoice or str(cells.get(8, "")).strip().upper() == "TOTAL":
            continue
        data.append(cells)

    # ---- verifikasi total sheet ----
    total_qty = sum(float(cells.get(10, 0) or 0) for cells in data)
    total_money = sum(float(cells.get(14, 0) or 0) for cells in data)
    print(f"baris item: {len(data)} | sum qty: {total_qty} | sum total: {total_money:,.2f}")

    # ---- katalog ----
    cats = []
    seen_cats = set()
    prods = {}
    for c in data:
        cat = str(c.get(5, "")).strip()
        name = str(c.get(6, "")).strip()
        unit = str(c.get(9, "")).strip()
        qty = float(c.get(10, 0) or 0)
        omzet = float(c.get(14, 0) or 0)
        if cat and cat not in seen_cats:
            seen_cats.add(cat)
            cats.append(cat)
        key = (cat, name)
        p = prods.setdefault(key, {"unit": unit.lower(), "qty": 0.0, "omzet": 0.0})
        p["qty"] += qty
        p["omzet"] += omzet

    products = []
    for (cat, name), p in prods.items():
        price = round(p["omzet"] / p["qty"]) if p["qty"] > 0 else 0
        products.append({
            "category": cat,
            "name": name,
            "unit": p["unit"],
            "price": price,
            "total_qty": round(p["qty"], 2),
            "total_omzet": round(p["omzet"], 2),
        })
    products.sort(key=lambda x: (x["category"], x["name"]))
    print(f"kategori: {len(cats)} | produk: {len(products)}")

    # ---- penjualan ----
    # Kolom XML: 0=invoice 1=tanggal 2=pelanggan 3=status_bayar 4=unit_usaha
    # 5=kategori 6=produk 7=ukuran 8=finishing 9=satuan 10=qty 11=harga_satuan
    # 12=biaya 13=harga_per_item 14=total
    orders = {}
    for c in data:
        invoice = str(c.get(0, "")).strip()
        o = orders.setdefault(invoice, {
            "invoice_no": invoice,
            "date": serial_to_date(c.get(1, "")),
            "customer": str(c.get(2, "")).strip(),
            "payment_status": str(c.get(3, "")).strip() or "Belum Bayar",
            "items": [],
        })
        o["items"].append({
            "product": str(c.get(6, "")).strip(),
            "category": str(c.get(5, "")).strip(),
            "unit": str(c.get(9, "")).strip(),
            "qty": float(c.get(10, 0) or 0),
            "unit_price": float(c.get(13, 0) or 0),  # Harga per Item
            "total": float(c.get(14, 0) or 0),
            "note": [str(c.get(7, "")).strip(), str(c.get(8, "")).strip()],
        })

    for o in orders.values():
        o["total"] = round(sum(i["total"] for i in o["items"]), 2)
        # urutkan item agar deterministik
        o["items"].sort(key=lambda i: (i["category"], i["product"]))

    ordered = sorted(orders.values(), key=lambda o: o["date"])
    print(f"invoice: {len(ordered)} | pelanggan: {len({o['customer'] for o in ordered})}")
    print(f"rentang tanggal: {ordered[0]['date']} .. {ordered[-1]['date']}")
    pay = {}
    for o in ordered:
        pay[o["payment_status"]] = pay.get(o["payment_status"], 0) + 1
    print("status bayar:", pay)

    sum_orders = sum(o["total"] for o in ordered)
    print(f"SUM total pesanan: {sum_orders:,.2f} | cocok dgn sheet: {abs(sum_orders - 39135000) < 1}")

    OUT.mkdir(parents=True, exist_ok=True)
    (OUT / "catalog.json").write_text(
        json.dumps({"categories": cats, "products": products}, ensure_ascii=False, indent=1),
        encoding="utf-8",
    )
    (OUT / "sales.json").write_text(
        json.dumps({"orders": ordered}, ensure_ascii=False, indent=1),
        encoding="utf-8",
    )
    print("OK: catalog.json + sales.json ditulis.")


if __name__ == "__main__":
    main()
