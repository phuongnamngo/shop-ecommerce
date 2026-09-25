// Local Docker load only. These numbers are not a production SLO.
// VU 1, duration 10s, threshold http_req_failed rate < 0.01.
// Required env: K6_BASE_URL, K6_VARIANT_ID, K6_SHIPPING_METHOD_ID,
// K6_SHIPPING_RATE_ID, K6_PROVINCE_CODE, K6_DISTRICT_CODE, K6_WARD_CODE.
// Does not migrate. A missing seed fails the request.

import http from 'k6/http';
import { check } from 'k6';

export const options = {
  vus: 1,
  duration: '10s',
  thresholds: {
    http_req_failed: ['rate<0.01'],
  },
};

const required = [
  'K6_BASE_URL',
  'K6_VARIANT_ID',
  'K6_SHIPPING_METHOD_ID',
  'K6_SHIPPING_RATE_ID',
  'K6_PROVINCE_CODE',
  'K6_DISTRICT_CODE',
  'K6_WARD_CODE',
];

function env(name) {
  const value = __ENV[name];
  if (!value) {
    throw new Error(`Missing ${name}`);
  }
  return value;
}

export function setup() {
  required.forEach(env);
}

export default function () {
  const base = env('K6_BASE_URL');
  const headers = { 'Content-Type': 'application/json', Accept: 'application/json' };

  const list = http.get(`${base}/api/v1/catalog/products`, { headers });
  check(list, { 'catalog list 200': (r) => r.status === 200 });

  const pdp = http.get(`${base}/api/v1/catalog/products/demo-tee`, { headers });
  check(pdp, { 'pdp 200': (r) => r.status === 200 });

  const cart = http.post(`${base}/api/v1/cart`, null, { headers });
  check(cart, { 'cart 201': (r) => r.status === 201 });
  const token = cart.json('meta.cart_token');

  const item = http.post(
    `${base}/api/v1/cart/items`,
    JSON.stringify({ product_variant_id: Number(env('K6_VARIANT_ID')), qty: 1 }),
    { headers: { ...headers, 'X-Cart-Token': token } },
  );
  check(item, { 'cart item 201': (r) => r.status === 201 });

  const checkout = http.post(
    `${base}/api/v1/checkout`,
    JSON.stringify({
      shipping_address: {
        recipient_name: 'A',
        phone: '0900000000',
        province_code: env('K6_PROVINCE_CODE'),
        district_code: env('K6_DISTRICT_CODE'),
        ward_code: env('K6_WARD_CODE'),
        address_line: 'Road',
      },
      shipping_method_id: Number(env('K6_SHIPPING_METHOD_ID')),
      shipping_rate_id: Number(env('K6_SHIPPING_RATE_ID')),
      payment_method_code: 'cod',
    }),
    { headers: { ...headers, 'X-Cart-Token': token } },
  );
  check(checkout, { 'checkout 201': (r) => r.status === 201 });
}
