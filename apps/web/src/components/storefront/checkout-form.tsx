"use client";

import { useRouter } from "next/navigation";
import { useEffect, useMemo, useState, type FormEvent } from "react";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  StorefrontBrowserError,
  storefrontErrorMessage,
} from "@/lib/api/storefront/browser";
import {
  clearCartToken,
  fetchCart,
  getCartToken,
  postCheckout,
} from "@/lib/api/storefront/cart";
import {
  listDistricts,
  listProvinces,
  listShippingMethods,
  listWards,
} from "@/lib/api/storefront/commerce";
import { formatVnd } from "@/lib/api/storefront/money";
import type {
  GeoNode,
  ShippingMethod,
  StorefrontCart,
} from "@/lib/api/storefront/types";

function rateFits(
  rate: { min_order_amount: string | null; max_order_amount: string | null },
  subtotal: number,
): boolean {
  if (rate.min_order_amount != null && subtotal < Number(rate.min_order_amount)) {
    return false;
  }
  if (rate.max_order_amount != null && subtotal > Number(rate.max_order_amount)) {
    return false;
  }
  return true;
}

export function CheckoutForm() {
  const router = useRouter();
  const [cart, setCart] = useState<StorefrontCart | null>(null);
  const [methods, setMethods] = useState<ShippingMethod[]>([]);
  const [provinces, setProvinces] = useState<GeoNode[]>([]);
  const [districts, setDistricts] = useState<GeoNode[]>([]);
  const [wards, setWards] = useState<GeoNode[]>([]);
  const [recipientName, setRecipientName] = useState("");
  const [phone, setPhone] = useState("");
  const [addressLine, setAddressLine] = useState("");
  const [provinceCode, setProvinceCode] = useState("");
  const [districtCode, setDistrictCode] = useState("");
  const [wardCode, setWardCode] = useState("");
  const [shippingKey, setShippingKey] = useState("");
  const [payment, setPayment] = useState<"cod" | "vnpay">("cod");
  const [coupon, setCoupon] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [pending, setPending] = useState(false);
  const [ready, setReady] = useState(false);

  useEffect(() => {
    if (!getCartToken()) {
      router.replace("/cart");
      return;
    }
    void (async () => {
      try {
        const [nextCart, nextMethods, nextProvinces] = await Promise.all([
          fetchCart(),
          listShippingMethods(),
          listProvinces(),
        ]);
        if (nextCart.items.length === 0) {
          router.replace("/cart");
          return;
        }
        setCart(nextCart);
        setMethods(nextMethods);
        setProvinces(nextProvinces);
        setReady(true);
      } catch (e) {
        if (
          e instanceof StorefrontBrowserError &&
          e.code === "CART_INVALID_TOKEN"
        ) {
          router.replace("/cart");
          return;
        }
        setError(storefrontErrorMessage(e));
        setReady(true);
      }
    })();
  }, [router]);

  const subtotal = Number(cart?.subtotal ?? 0);
  const options = useMemo(() => {
    return methods.flatMap((method) =>
      method.rates
        .filter((rate) => rateFits(rate, subtotal))
        .map((rate) => ({
          key: `${method.id}:${rate.id}`,
          methodId: method.id,
          rateId: rate.id,
          name: method.name,
          price: rate.price,
        })),
    );
  }, [methods, subtotal]);

  const selected = options.find((option) => option.key === shippingKey) ?? options[0];

  async function onSubmit(event: FormEvent) {
    event.preventDefault();
    if (!cart || !selected) return;
    setPending(true);
    setError(null);
    try {
      const created = await postCheckout({
        shipping_address: {
          recipient_name: recipientName,
          phone,
          province_code: provinceCode,
          district_code: districtCode,
          ward_code: wardCode,
          address_line: addressLine,
        },
        shipping_method_id: selected.methodId,
        shipping_rate_id: selected.rateId,
        payment_method_code: payment,
        ...(coupon.trim() !== "" ? { coupon_code: coupon.trim() } : {}),
      });
      clearCartToken();
      if (payment === "vnpay" && created.payment.redirect_url) {
        window.location.assign(created.payment.redirect_url);
        return;
      }
      const token = created.lookup_token;
      router.push(
        token
          ? `/checkout/result?token=${encodeURIComponent(token)}`
          : `/checkout/result?number=${encodeURIComponent(created.number)}&status=${encodeURIComponent(created.status)}`,
      );
    } catch (e) {
      setError(storefrontErrorMessage(e));
      setPending(false);
    }
  }

  if (!ready) {
    return (
      <main className="mx-auto max-w-6xl px-4 py-10">
        <h1 className="text-2xl font-semibold">Thanh toán</h1>
        <p className="mt-4 text-sm text-zinc-600">Đang tải…</p>
      </main>
    );
  }

  return (
    <main className="mx-auto max-w-6xl px-4 py-10">
      <h1 className="text-2xl font-semibold">Thanh toán</h1>
      {error ? (
        <p className="mt-4 text-sm text-red-700" role="alert">
          {error}
        </p>
      ) : null}
      <form
        onSubmit={(event) => void onSubmit(event)}
        className="mt-8 grid gap-10 lg:grid-cols-[1fr_20rem]"
      >
        <div className="space-y-6">
          <fieldset className="space-y-3">
            <legend className="text-sm font-medium">Địa chỉ giao hàng</legend>
            <div>
              <Label htmlFor="recipient_name">Người nhận</Label>
              <Input
                id="recipient_name"
                className="mt-1"
                required
                value={recipientName}
                onChange={(e) => setRecipientName(e.target.value)}
              />
            </div>
            <div>
              <Label htmlFor="phone">Số điện thoại</Label>
              <Input
                id="phone"
                className="mt-1"
                required
                value={phone}
                onChange={(e) => setPhone(e.target.value)}
              />
            </div>
            <div>
              <Label htmlFor="province">Tỉnh / thành</Label>
              <select
                id="province"
                required
                className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                value={provinceCode}
                onChange={(e) => {
                  const code = e.target.value;
                  setProvinceCode(code);
                  setDistrictCode("");
                  setWardCode("");
                  setDistricts([]);
                  setWards([]);
                  if (!code) return;
                  void listDistricts(code)
                    .then(setDistricts)
                    .catch((err) => setError(storefrontErrorMessage(err)));
                }}
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
                disabled={!provinceCode}
                className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                value={districtCode}
                onChange={(e) => {
                  const code = e.target.value;
                  setDistrictCode(code);
                  setWardCode("");
                  setWards([]);
                  if (!code) return;
                  void listWards(code)
                    .then(setWards)
                    .catch((err) => setError(storefrontErrorMessage(err)));
                }}
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
                disabled={!districtCode}
                className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                value={wardCode}
                onChange={(e) => setWardCode(e.target.value)}
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
                className="mt-1"
                required
                value={addressLine}
                onChange={(e) => setAddressLine(e.target.value)}
              />
            </div>
          </fieldset>

          <fieldset className="space-y-3">
            <legend className="text-sm font-medium">Vận chuyển</legend>
            {options.length === 0 ? (
              <p className="text-sm text-zinc-600">
                Không có phương thức vận chuyển phù hợp.
              </p>
            ) : (
              options.map((option) => (
                <label key={option.key} className="flex items-center gap-2 text-sm">
                  <input
                    type="radio"
                    name="shipping"
                    value={option.key}
                    checked={(shippingKey || options[0]?.key) === option.key}
                    onChange={() => setShippingKey(option.key)}
                  />
                  <span>
                    {option.name} — {formatVnd(option.price)}
                  </span>
                </label>
              ))
            )}
          </fieldset>

          <fieldset className="space-y-3">
            <legend className="text-sm font-medium">Thanh toán</legend>
            <label className="flex items-center gap-2 text-sm">
              <input
                type="radio"
                name="payment"
                value="cod"
                checked={payment === "cod"}
                onChange={() => setPayment("cod")}
              />
              Thanh toán khi nhận hàng (COD)
            </label>
            <label className="flex items-center gap-2 text-sm">
              <input
                type="radio"
                name="payment"
                value="vnpay"
                checked={payment === "vnpay"}
                onChange={() => setPayment("vnpay")}
              />
              VNPay
            </label>
          </fieldset>

          <div>
            <Label htmlFor="coupon">Mã giảm giá</Label>
            <Input
              id="coupon"
              className="mt-1"
              value={coupon}
              onChange={(e) => setCoupon(e.target.value)}
            />
          </div>
        </div>

        <aside className="h-fit space-y-3 rounded-lg border p-4">
          <p className="flex justify-between text-sm">
            <span>Tạm tính</span>
            <span>{formatVnd(cart?.subtotal ?? 0)}</span>
          </p>
          <p className="flex justify-between text-sm">
            <span>Phí vận chuyển</span>
            <span>{selected ? formatVnd(selected.price) : "—"}</span>
          </p>
          <Button
            type="submit"
            className="w-full"
            disabled={pending || !selected || options.length === 0}
          >
            {pending ? "Đang đặt hàng…" : "Đặt hàng"}
          </Button>
        </aside>
      </form>
    </main>
  );
}
