"use client";

import Image from "next/image";
import Link from "next/link";
import { FormEvent, useEffect, useState } from "react";

import { Button } from "@/components/ui/button";
import { absoluteMediaUrl } from "@/lib/api/storefront/client";
import {
  StorefrontBrowserError,
  storefrontErrorMessage,
} from "@/lib/api/storefront/browser";
import {
  createReview,
  fetchReviewEligibility,
  listProductReviews,
  updateReview,
  type PublicProductReview,
  type PublicReviewsMeta,
  type ReviewEligibility,
} from "@/lib/api/storefront/reviews";
import type { PublicProductDetail } from "@/lib/api/storefront/types";

function appendReviewForm(
  form: FormData,
  rating: number,
  body: string,
  variantId: number | undefined,
  files: FileList | null,
) {
  form.append("rating", String(rating));
  if (body.trim() !== "") {
    form.append("body", body.trim());
  }
  if (variantId) {
    form.append("product_variant_id", String(variantId));
  }
  if (files) {
    for (let i = 0; i < Math.min(files.length, 3); i += 1) {
      form.append("images[]", files[i]!);
    }
  }
}

function ReviewForm({
  submitLabel,
  onSubmit,
  pending,
  error,
}: {
  submitLabel: string;
  onSubmit: (rating: number, body: string, files: FileList | null) => void;
  pending: boolean;
  error: string | null;
}) {
  const [rating, setRating] = useState(5);
  const [body, setBody] = useState("");

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const files = (event.currentTarget.elements.namedItem("images") as HTMLInputElement)
      ?.files;
    onSubmit(rating, body, files);
  }

  return (
    <form className="space-y-3" onSubmit={handleSubmit}>
      <fieldset>
        <legend className="text-sm font-semibold">Đánh giá</legend>
        <div className="mt-2 flex gap-1">
          {[1, 2, 3, 4, 5].map((star) => (
            <button
              key={star}
              type="button"
              className={star <= rating ? "text-amber-500" : "text-slate-300"}
              aria-label={`${star} sao`}
              onClick={() => setRating(star)}
            >
              ★
            </button>
          ))}
        </div>
      </fieldset>
      <textarea
        name="body"
        rows={4}
        className="w-full rounded-lg border border-slate-200 p-3 text-sm"
        placeholder="Chia sẻ trải nghiệm của bạn (không bắt buộc)"
        value={body}
        onChange={(e) => setBody(e.target.value)}
      />
      <input name="images" type="file" accept="image/jpeg,image/png,image/webp" multiple />
      {error ? (
        <p className="text-sm text-red-700" role="alert">
          {error}
        </p>
      ) : null}
      <Button type="submit" disabled={pending} className="rounded-lg bg-blue-600 hover:bg-blue-700">
        {pending ? "Đang gửi…" : submitLabel}
      </Button>
    </form>
  );
}

export function ProductReviews({
  product,
  selectedVariantId,
}: {
  product: PublicProductDetail;
  selectedVariantId?: number;
}) {
  const [page, setPage] = useState(1);
  const [reviews, setReviews] = useState<PublicProductReview[]>([]);
  const [meta, setMeta] = useState<PublicReviewsMeta | null>(null);
  const [eligibility, setEligibility] = useState<ReviewEligibility | null>(null);
  const [guest, setGuest] = useState(false);
  const [pending, setPending] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [done, setDone] = useState(false);

  useEffect(() => {
    const frame = requestAnimationFrame(() => {
      void listProductReviews(product.slug, page)
        .then((res) => {
          setReviews(res.data);
          setMeta(res.meta);
        })
        .catch(() => {
          setReviews([]);
        });
    });
    return () => cancelAnimationFrame(frame);
  }, [product.slug, page, done]);

  useEffect(() => {
    const frame = requestAnimationFrame(() => {
      void fetchReviewEligibility(product.id)
        .then((res) => {
          setEligibility(res);
          setGuest(false);
        })
        .catch((err) => {
          if (err instanceof StorefrontBrowserError && err.status === 401) {
            setGuest(true);
            setEligibility(null);
            return;
          }
          setEligibility(null);
        });
    });
    return () => cancelAnimationFrame(frame);
  }, [product.id, done]);

  async function onCreate(rating: number, body: string, files: FileList | null) {
    setPending(true);
    setError(null);
    try {
      const form = new FormData();
      form.append("product_id", String(product.id));
      appendReviewForm(form, rating, body, selectedVariantId, files);
      await createReview(form);
      setDone((v) => !v);
    } catch (err) {
      setError(storefrontErrorMessage(err));
    } finally {
      setPending(false);
    }
  }

  async function onUpdate(rating: number, body: string, files: FileList | null) {
    const id = eligibility?.existing_review?.id;
    if (!id) return;
    setPending(true);
    setError(null);
    try {
      const form = new FormData();
      appendReviewForm(form, rating, body, selectedVariantId, files);
      await updateReview(id, form);
      setDone((v) => !v);
    } catch (err) {
      setError(storefrontErrorMessage(err));
    } finally {
      setPending(false);
    }
  }

  const count = meta?.rating_count ?? product.rating_count ?? 0;
  const avg = meta?.rating_avg ?? product.rating_avg ?? null;

  return (
    <section id="reviews" className="mt-16 scroll-mt-24">
      <h2 className="text-xl font-semibold">Đánh giá</h2>
      {count > 0 && avg != null ? (
        <p className="mt-1 text-sm text-slate-600">
          ★ {avg} ({count})
        </p>
      ) : (
        <p className="mt-1 text-sm text-slate-500">Chưa có đánh giá công khai.</p>
      )}

      <div className="mt-6 rounded-xl border border-slate-200 bg-white p-4">
        {guest ? (
          <p className="text-sm text-slate-600">
            <Link
              href={`/login?next=${encodeURIComponent(`/products/${product.slug}`)}`}
              className="font-semibold text-blue-600"
            >
              Đăng nhập
            </Link>{" "}
            để viết đánh giá sau khi mua hàng.
          </p>
        ) : null}
        {eligibility?.reason === "ok" ? (
          <ReviewForm
            submitLabel="Gửi đánh giá"
            onSubmit={(rating, body, files) => void onCreate(rating, body, files)}
            pending={pending}
            error={error}
          />
        ) : null}
        {eligibility?.reason === "pending" ? (
          <p className="text-sm text-slate-600">Đang chờ duyệt</p>
        ) : null}
        {eligibility?.reason === "rejected" ? (
          <div className="space-y-2">
            <p className="text-sm text-slate-600">
              Đánh giá chưa được duyệt. Bạn có thể sửa và gửi lại.
            </p>
            <ReviewForm
              submitLabel="Gửi lại"
              onSubmit={(rating, body, files) => void onUpdate(rating, body, files)}
              pending={pending}
              error={error}
            />
          </div>
        ) : null}
        {eligibility?.reason === "approved" ? (
          <p className="text-sm text-slate-600">Bạn đã đánh giá sản phẩm này.</p>
        ) : null}
        {eligibility?.reason === "not_purchased" ? (
          <p className="text-sm text-slate-600">
            Mua sản phẩm này rồi mới đánh giá được.
          </p>
        ) : null}
      </div>

      <ul className="mt-6 space-y-4">
        {reviews.map((review) => (
          <li key={review.id} className="rounded-xl border border-slate-200 bg-white p-4">
            <p className="text-sm font-semibold">
              {review.author_name}{" "}
              <span className="font-normal text-amber-600">★ {review.rating}</span>
            </p>
            {review.variant_label ? (
              <p className="text-xs text-slate-500">{review.variant_label}</p>
            ) : null}
            {review.body ? (
              <p className="mt-2 text-sm text-slate-700">{review.body}</p>
            ) : null}
            {(review.images ?? []).length > 0 ? (
              <ul className="mt-3 flex gap-2">
                {(review.images ?? []).map((image, index) => {
                  const src = absoluteMediaUrl(image.thumbnail_url || image.url);
                  if (!src) return null;
                  return (
                    <li key={`${review.id}-${index}`} className="relative h-16 w-16 overflow-hidden rounded-lg">
                      <Image src={src} alt="" fill className="object-cover" />
                    </li>
                  );
                })}
              </ul>
            ) : null}
          </li>
        ))}
      </ul>
      {meta && meta.last_page > 1 ? (
        <div className="mt-4 flex gap-2">
          <Button
            type="button"
            variant="outline"
            disabled={page <= 1}
            onClick={() => setPage((p) => p - 1)}
          >
            Trước
          </Button>
          <Button
            type="button"
            variant="outline"
            disabled={page >= meta.last_page}
            onClick={() => setPage((p) => p + 1)}
          >
            Sau
          </Button>
        </div>
      ) : null}
    </section>
  );
}
