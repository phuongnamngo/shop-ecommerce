export type ApiSuccess<T> = {
  data: T;
  meta: Record<string, unknown>;
};

export type ApiErrorItem = {
  code: string;
  message: string;
  field?: string | null;
};

export type ApiErrorBody = {
  data: null;
  meta?: Record<string, unknown>;
  errors: ApiErrorItem[];
};

export type AdminProfile = {
  id: number;
  code: string;
  name: string;
  email: string;
  status: string;
  roles: string[];
};
