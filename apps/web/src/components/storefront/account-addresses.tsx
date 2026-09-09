"use client";

import { FormEvent, useEffect, useState } from "react";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { storefrontErrorMessage } from "@/lib/api/storefront/browser";
import {
  listDistricts,
  listProvinces,
  listWards,
} from "@/lib/api/storefront/commerce";
import {
  createCustomerAddress,
  deleteCustomerAddress,
  listCustomerAddresses,
  updateCustomerAddress,
} from "@/lib/api/storefront/customer";
import type {
  CustomerAddress,
  CustomerAddressBody,
  GeoNode,
} from "@/lib/api/storefront/types";

const emptyForm = {
  label: "",
  recipient_name: "",
  phone: "",
  province_code: "",
  district_code: "",
  ward_code: "",
  address_line: "",
  postal_code: "",
  is_default: false,
};

export function AccountAddresses() {
  const [rows, setRows] = useState<CustomerAddress[]>([]);
  const [provinces, setProvinces] = useState<GeoNode[]>([]);
  const [districts, setDistricts] = useState<GeoNode[]>([]);
  const [wards, setWards] = useState<GeoNode[]>([]);
  const [form, setForm] = useState(emptyForm);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [pending, setPending] = useState(false);

  async function refresh() {
    const [list, nextProvinces] = await Promise.all([
      listCustomerAddresses(),
      listProvinces(),
    ]);
    setRows(list);
    setProvinces(nextProvinces);
  }

  useEffect(() => {
    const frame = requestAnimationFrame(() => {
      void refresh().catch((err) => setError(storefrontErrorMessage(err)));
    });
    return () => cancelAnimationFrame(frame);
  }, []);

  function resetForm() {
    setForm(emptyForm);
    setEditingId(null);
    setDistricts([]);
    setWards([]);
  }

  async function loadCascade(province: string, district: string) {
    if (!province) {
      setDistricts([]);
      setWards([]);
      return;
    }
    const nextDistricts = await listDistricts(province);
    setDistricts(nextDistricts);
    if (!district) {
      setWards([]);
      return;
    }
    setWards(await listWards(district));
  }

  async function startEdit(row: CustomerAddress) {
    setEditingId(row.id);
    setForm({
      label: row.label ?? "",
      recipient_name: row.recipient_name,
      phone: row.phone,
      province_code: row.province_code,
      district_code: row.district_code,
      ward_code: row.ward_code,
      address_line: row.address_line,
      postal_code: row.postal_code ?? "",
      is_default: row.is_default,
    });
    setError(null);
    try {
      await loadCascade(row.province_code, row.district_code);
    } catch (err) {
      setError(storefrontErrorMessage(err));
    }
  }

  async function onProvince(code: string) {
    setForm((current) => ({
      ...current,
      province_code: code,
      district_code: "",
      ward_code: "",
    }));
    setWards([]);
    if (!code) {
      setDistricts([]);
      return;
    }
    try {
      setDistricts(await listDistricts(code));
    } catch (err) {
      setError(storefrontErrorMessage(err));
    }
  }

  async function onDistrict(code: string) {
    setForm((current) => ({
      ...current,
      district_code: code,
      ward_code: "",
    }));
    if (!code) {
      setWards([]);
      return;
    }
    try {
      setWards(await listWards(code));
    } catch (err) {
      setError(storefrontErrorMessage(err));
    }
  }

  async function onSubmit(event: FormEvent) {
    event.preventDefault();
    setPending(true);
    setError(null);
    const body: CustomerAddressBody = {
      label: form.label.trim() === "" ? null : form.label.trim(),
      recipient_name: form.recipient_name,
      phone: form.phone,
      province_code: form.province_code,
      district_code: form.district_code,
      ward_code: form.ward_code,
      address_line: form.address_line,
      postal_code: form.postal_code.trim() === "" ? null : form.postal_code.trim(),
      is_default: form.is_default,
    };
    try {
      if (editingId) {
        await updateCustomerAddress(editingId, body);
      } else {
        await createCustomerAddress(body);
      }
      resetForm();
      await refresh();
    } catch (err) {
      setError(storefrontErrorMessage(err));
    } finally {
      setPending(false);
    }
  }

  async function onDelete(id: number) {
    setPending(true);
    setError(null);
    try {
      await deleteCustomerAddress(id);
      if (editingId === id) resetForm();
      await refresh();
    } catch (err) {
      setError(storefrontErrorMessage(err));
    } finally {
      setPending(false);
    }
  }

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-semibold">Địa chỉ</h1>
      {error ? (
        <p className="text-sm text-red-700" role="alert">
          {error}
        </p>
      ) : null}
      {rows.length === 0 ? (
        <p className="text-sm text-zinc-600">Chưa có địa chỉ lưu.</p>
      ) : (
        <ul className="space-y-3">
          {rows.map((row) => (
            <li key={row.id} className="rounded-lg border p-3 text-sm">
              <p className="font-medium">
                {row.label ?? "Địa chỉ"}{" "}
                {row.is_default ? (
                  <span className="text-zinc-500">(mặc định)</span>
                ) : null}
              </p>
              <p className="mt-1 text-zinc-600">
                {row.recipient_name} · {row.phone}
              </p>
              <p className="text-zinc-600">{row.address_line}</p>
              <div className="mt-2 flex gap-2">
                <Button
                  type="button"
                  size="sm"
                  variant="outline"
                  onClick={() => void startEdit(row)}
                >
                  Sửa
                </Button>
                <Button
                  type="button"
                  size="sm"
                  variant="ghost"
                  disabled={pending}
                  onClick={() => void onDelete(row.id)}
                >
                  Xóa
                </Button>
              </div>
            </li>
          ))}
        </ul>
      )}

      <form className="max-w-md space-y-3" onSubmit={(e) => void onSubmit(e)}>
        <h2 className="font-medium">
          {editingId ? "Sửa địa chỉ" : "Thêm địa chỉ"}
        </h2>
        <div>
          <Label htmlFor="label">Nhãn</Label>
          <Input
            id="label"
            className="mt-1"
            value={form.label}
            onChange={(e) => setForm({ ...form, label: e.target.value })}
          />
        </div>
        <div>
          <Label htmlFor="recipient_name">Người nhận</Label>
          <Input
            id="recipient_name"
            required
            className="mt-1"
            value={form.recipient_name}
            onChange={(e) =>
              setForm({ ...form, recipient_name: e.target.value })
            }
          />
        </div>
        <div>
          <Label htmlFor="phone">Số điện thoại</Label>
          <Input
            id="phone"
            required
            className="mt-1"
            value={form.phone}
            onChange={(e) => setForm({ ...form, phone: e.target.value })}
          />
        </div>
        <div>
          <Label htmlFor="province">Tỉnh / thành</Label>
          <select
            id="province"
            required
            className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm"
            value={form.province_code}
            onChange={(e) => void onProvince(e.target.value)}
          >
            <option value="">Chọn tỉnh / thành</option>
            {provinces.map((row) => (
              <option key={row.code} value={row.code}>
                {row.name}
              </option>
            ))}
          </select>
        </div>
        <div>
          <Label htmlFor="district">Quận / huyện</Label>
          <select
            id="district"
            required
            disabled={!form.province_code}
            className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm"
            value={form.district_code}
            onChange={(e) => void onDistrict(e.target.value)}
          >
            <option value="">Chọn quận / huyện</option>
            {districts.map((row) => (
              <option key={row.code} value={row.code}>
                {row.name}
              </option>
            ))}
          </select>
        </div>
        <div>
          <Label htmlFor="ward">Phường / xã</Label>
          <select
            id="ward"
            required
            disabled={!form.district_code}
            className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm"
            value={form.ward_code}
            onChange={(e) =>
              setForm({ ...form, ward_code: e.target.value })
            }
          >
            <option value="">Chọn phường / xã</option>
            {wards.map((row) => (
              <option key={row.code} value={row.code}>
                {row.name}
              </option>
            ))}
          </select>
        </div>
        <div>
          <Label htmlFor="address_line">Địa chỉ</Label>
          <Input
            id="address_line"
            required
            className="mt-1"
            value={form.address_line}
            onChange={(e) =>
              setForm({ ...form, address_line: e.target.value })
            }
          />
        </div>
        <label className="flex items-center gap-2 text-sm">
          <input
            type="checkbox"
            checked={form.is_default}
            onChange={(e) =>
              setForm({ ...form, is_default: e.target.checked })
            }
          />
          Đặt làm mặc định
        </label>
        <div className="flex gap-2">
          <Button type="submit" disabled={pending}>
            {pending ? "Đang lưu…" : editingId ? "Cập nhật" : "Thêm"}
          </Button>
          {editingId ? (
            <Button type="button" variant="ghost" onClick={resetForm}>
              Hủy
            </Button>
          ) : null}
        </div>
      </form>
    </div>
  );
}
