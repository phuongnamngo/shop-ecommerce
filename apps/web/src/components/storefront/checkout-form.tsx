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
  fetchActiveCart,
  getCartToken,
  postCheckout,
} from "@/lib/api/storefront/cart";
import {
  listDistricts,
  listProvinces,
  listShippingMethods,
  listWards,
} from "@/lib/api/storefront/commerce";
import {
  createCustomerAddress,
  fetchCustomerMeOrNull,
  listCustomerAddresses,
} from "@/lib/api/storefront/customer";
import { formatVnd } from "@/lib/api/storefront/money";
import type {
  CustomerAddress,
  CustomerProfile,
  GeoNode,
  ShippingMethod,
  StorefrontCart,
} from "@/lib/api/storefront/types";
import { sfContainer, sfInput } from "@/lib/storefront/ui";
import { cn } from "@/lib/utils";

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
  const [me, setMe] = useState<CustomerProfile | null>(null);
  const [addresses, setAddresses] = useState<CustomerAddress[]>([]);
  const [addressMode, setAddressMode] = useState<"saved" | "new">("new");
  const [savedId, setSavedId] = useState<number | null>(null);
  const [saveToBook, setSaveToBook] = useState(false);
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

  const showNewForm = !me || addressMode === "new";

  useEffect(() => {
    void (async () => {
      try {
        const profile = await fetchCustomerMeOrNull();
        setMe(profile);
        if (!profile && !getCartToken()) {
          router.replace("/cart");
          return;
        }
        const nextCart = await fetchActiveCart();
        if (nextCart.items.length === 0) {
          router.replace("/cart");
          return;
        }
        const [nextMethods, nextProvinces] = await Promise.all([
          listShippingMethods(),
          listProvinces(),
        ]);
        setCart(nextCart);
        setMethods(nextMethods);
        setProvinces(nextProvinces);
        if (profile) {
          const book = await listCustomerAddresses();
          setAddresses(book);
          if (book.length > 0) {
            const preferred =
              book.find((row) => row.is_default)?.id ?? book[0].id;
            setAddressMode("saved");
            setSavedId(preferred);
          }
        }
        setReady(true);
      } catch (e) {
        if (
          e instanceof StorefrontBrowserError &&
          (e.code === "CART_INVALID_TOKEN" || e.status === 401)
        ) {
          router.replace(e.status === 401 ? "/login" : "/cart");
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
      const shipping = {
        shipping_method_id: selected.methodId,
        shipping_rate_id: selected.rateId,
        payment_method_code: payment,
        ...(coupon.trim() !== "" ? { coupon_code: coupon.trim() } : {}),
      };
      const newAddress = {
        recipient_name: recipientName,
        phone,
        province_code: provinceCode,
        district_code: districtCode,
        ward_code: wardCode,
        address_line: addressLine,
      };

      let created;
      if (me && addressMode === "saved" && savedId) {
        created = await postCheckout({
          customer_address_id: savedId,
          ...shipping,
        });
      } else if (me && saveToBook) {
        const saved = await createCustomerAddress({
          ...newAddress,
          is_default: addresses.length === 0,
        });
        created = await postCheckout({
          customer_address_id: saved.id,
          ...shipping,
        });
      } else {
        created = await postCheckout({
          shipping_address: newAddress,
          ...shipping,
        });
      }

      if (!me) {
        clearCartToken();
      }
      if (payment === "vnpay" && created.payment.redirect_url) {
        window.location.assign(created.payment.redirect_url);
        return;
      }
      if (me) {
        router.push(`/account/orders/${created.id}`);
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
      <main className={`${sfContainer} py-10`}>
        <h1 className="text-2xl font-semibold">Thanh toán</h1>
        <p className="mt-4 text-sm text-slate-500">Đang tải…</p>
      </main>
    );
  }

  return (
    <main className={`${sfContainer} py-8 lg:py-10`}>
      <p className="text-center text-xs font-semibold uppercase tracking-[0.16em] text-emerald-700">
        Thanh toán an toàn 100%
      </p>
      <ol className="mx-auto mt-4 flex max-w-xl items-center justify-center gap-2 text-xs font-medium text-slate-400">
        <li className="text-blue-600">1. Giỏ hàng</li>
        <li>/</li>
        <li className="text-slate-950">2. Thông tin giao hàng</li>
        <li>/</li>
        <li>3. Thanh toán</li>
      </ol>
      <h1 className="mt-6 text-3xl font-bold tracking-tight">Thanh toán</h1>
      {error ? (
        <p className="mt-4 text-sm text-red-700" role="alert">
          {error}
        </p>
      ) : null}
      <form
        onSubmit={(event) => void onSubmit(event)}
        className="mt-8 grid gap-8 lg:grid-cols-[1fr_22rem]"
      >
        <div className="space-y-5">
          <fieldset className="space-y-4 rounded-xl border border-slate-200 bg-white p-5">
            <legend className="px-1 text-sm font-semibold">Địa chỉ giao hàng</legend>
            {me && addresses.length > 0 ? (
              <div className="space-y-2">
                {addresses.map((row) => (
                  <label
                    key={row.id}
                    className={cn(
                      "flex items-start gap-3 rounded-lg border p-3 text-sm",
                      addressMode === "saved" && savedId === row.id
                        ? "border-blue-600 bg-blue-50"
                        : "border-slate-200",
                    )}
                  >
                    <input
                      type="radio"
                      name="address_mode"
                      className="mt-1"
                      checked={addressMode === "saved" && savedId === row.id}
                      onChange={() => {
                        setAddressMode("saved");
                        setSavedId(row.id);
                      }}
                    />
                    <span>
                      <span className="font-medium">{row.recipient_name}</span>
                      {" · "}
                      {row.phone}
                      <br />
                      {row.address_line}
                      {row.is_default ? (
                        <span className="ml-2 rounded-full bg-blue-100 px-2 py-0.5 text-[11px] font-semibold text-blue-700">
                          Mặc định
                        </span>
                      ) : null}
                    </span>
                  </label>
                ))}
                <label className="flex items-center gap-2 text-sm">
                  <input
                    type="radio"
                    name="address_mode"
                    checked={addressMode === "new"}
                    onChange={() => setAddressMode("new")}
                  />
                  Dùng địa chỉ mới
                </label>
              </div>
            ) : null}

            {showNewForm ? (
              <div className="grid gap-4 sm:grid-cols-2">
                <div className="sm:col-span-1">
                  <Label htmlFor="recipient_name">Người nhận</Label>
                  <Input
                    id="recipient_name"
                    className={sfInput}
                    required
                    value={recipientName}
                    onChange={(e) => setRecipientName(e.target.value)}
                  />
                </div>
                <div>
                  <Label htmlFor="phone">Số điện thoại</Label>
                  <Input
                    id="phone"
                    className={sfInput}
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
                    className={sfInput}
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
                    className={sfInput}
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
                    className={sfInput}
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
                <div className="sm:col-span-2">
                  <Label htmlFor="address_line">Địa chỉ</Label>
                  <Input
                    id="address_line"
                    className={sfInput}
                    required
                    value={addressLine}
                    onChange={(e) => setAddressLine(e.target.value)}
                  />
                </div>
                {me ? (
                  <label className="flex items-center gap-2 text-sm sm:col-span-2">
                    <input
                      type="checkbox"
                      checked={saveToBook}
                      onChange={(e) => setSaveToBook(e.target.checked)}
                    />
                    Lưu vào sổ địa chỉ
                  </label>
                ) : null}
              </div>
            ) : null}
          </fieldset>

          <fieldset className="space-y-3 rounded-xl border border-slate-200 bg-white p-5">
            <legend className="px-1 text-sm font-semibold">Vận chuyển</legend>
            {options.length === 0 ? (
              <p className="text-sm text-slate-500">
                Không có phương thức vận chuyển phù hợp.
              </p>
            ) : (
              options.map((option) => (
                <label
                  key={option.key}
                  className={cn(
                    "flex items-center justify-between gap-3 rounded-lg border p-3 text-sm",
                    (shippingKey || options[0]?.key) === option.key
                      ? "border-blue-600 bg-blue-50"
                      : "border-slate-200",
                  )}
                >
                  <span className="flex items-center gap-2">
                    <input
                      type="radio"
                      name="shipping"
                      value={option.key}
                      checked={(shippingKey || options[0]?.key) === option.key}
                      onChange={() => setShippingKey(option.key)}
                    />
                    {option.name}
                  </span>
                  <span className="font-semibold">{formatVnd(option.price)}</span>
                </label>
              ))
            )}
          </fieldset>

          <fieldset className="space-y-3 rounded-xl border border-slate-200 bg-white p-5">
            <legend className="px-1 text-sm font-semibold">Thanh toán</legend>
            <label
              className={cn(
                "flex items-start gap-3 rounded-lg border p-3 text-sm",
                payment === "cod" ? "border-blue-600 bg-blue-50" : "border-slate-200",
              )}
            >
              <input
                type="radio"
                name="payment"
                value="cod"
                className="mt-1"
                checked={payment === "cod"}
                onChange={() => setPayment("cod")}
              />
              <span>
                <span className="font-medium">Thanh toán khi nhận hàng (COD)</span>
                <span className="mt-1 block text-xs text-slate-500">
                  Thanh toán tiền mặt khi nhận hàng.
                </span>
              </span>
            </label>
            <label
              className={cn(
                "flex items-start gap-3 rounded-lg border p-3 text-sm",
                payment === "vnpay" ? "border-blue-600 bg-blue-50" : "border-slate-200",
              )}
            >
              <input
                type="radio"
                name="payment"
                value="vnpay"
                className="mt-1"
                checked={payment === "vnpay"}
                onChange={() => setPayment("vnpay")}
              />
              <span>
                <span className="font-medium">VNPay</span>
                <span className="mt-1 block text-xs text-slate-500">
                  Thanh toán qua cổng VNPay.
                </span>
              </span>
            </label>
          </fieldset>

          <div className="rounded-xl border border-slate-200 bg-white p-5">
            <Label htmlFor="coupon">Mã giảm giá</Label>
            <Input
              id="coupon"
              className={sfInput}
              value={coupon}
              onChange={(e) => setCoupon(e.target.value)}
              placeholder="Nhập mã nếu có"
            />
          </div>
        </div>

        <aside className="h-fit space-y-3 rounded-xl border border-slate-200 bg-white p-5 lg:sticky lg:top-24">
          <h2 className="font-semibold">Đơn hàng của bạn</h2>
          <p className="flex justify-between text-sm">
            <span className="text-slate-500">Tạm tính</span>
            <span>{formatVnd(cart?.subtotal ?? 0)}</span>
          </p>
          <p className="flex justify-between text-sm">
            <span className="text-slate-500">Phí vận chuyển</span>
            <span>{selected ? formatVnd(selected.price) : "—"}</span>
          </p>
          <p className="flex justify-between border-t border-slate-100 pt-3 font-semibold">
            <span>Tổng cộng</span>
            <span>
              {formatVnd(
                Number(cart?.subtotal ?? 0) + Number(selected?.price ?? 0),
              )}
            </span>
          </p>
          <Button
            type="submit"
            className="h-12 w-full rounded-lg bg-blue-600 font-semibold hover:bg-blue-700"
            disabled={pending || !selected || options.length === 0}
          >
            {pending ? "Đang đặt hàng…" : "Đặt hàng ngay"}
          </Button>
        </aside>
      </form>
    </main>
  );
}
