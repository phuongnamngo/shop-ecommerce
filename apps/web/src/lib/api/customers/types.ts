export type PageMeta = {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
};

export type AdminCustomerAddress = {
  id: number;
  label: string | null;
  recipient_name: string;
  phone: string;
  province_code: string;
  district_code: string;
  ward_code: string;
  address_line: string;
  postal_code: string | null;
  is_default: boolean;
};

export type AdminCustomer = {
  id: number;
  code: string;
  name: string;
  email: string;
  phone: string | null;
  status: string;
  created_at: string | null;
  last_login_at: string | null;
  email_verified_at: string | null;
  addresses?: AdminCustomerAddress[];
};
