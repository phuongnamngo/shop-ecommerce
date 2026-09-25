import { apiFetch, ApiError } from "@/lib/api/client";
import type { AdminProfile, ApiSuccess } from "@/lib/api/types";

export function messageForAuthError(error: unknown): string {
  if (!(error instanceof ApiError)) {
    return "Không kết nối được máy chủ. Thử lại sau.";
  }

  switch (error.code) {
    case "AUTH_INVALID_CREDENTIALS":
      return "Email hoặc mật khẩu không đúng.";
    case "AUTH_ACCOUNT_INACTIVE":
      return "Tài khoản đang inactive.";
    case "AUTH_ACCOUNT_BANNED":
      return "Tài khoản đã bị khóa.";
    case "AUTH_THROTTLED":
      return "Thử quá nhiều lần. Đợi một lát rồi thử lại.";
    case "AUTH_RESET_TOKEN_INVALID":
      return "Link đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.";
    case "AUTH_FORBIDDEN":
      return "Bạn không có quyền truy cập Admin.";
    case "AUTH_UNAUTHENTICATED":
      return "Phiên đăng nhập đã hết. Đăng nhập lại.";
    case "AUTH_TWO_FACTOR_INVALID":
      return "Mã xác thực không đúng.";
    default:
      return error.message || `Lỗi ${error.status}`;
  }
}

export function adminLogin(input: {
  email: string;
  password: string;
}): Promise<ApiSuccess<AdminProfile>> {
  return apiFetch<AdminProfile>("/api/v1/admin/auth/login", {
    method: "POST",
    json: input,
  });
}

export async function adminLogout(): Promise<void> {
  try {
    await apiFetch("/api/v1/admin/auth/logout", { method: "POST", json: {} });
  } catch (error) {
    if (error instanceof ApiError && error.status === 401) {
      return;
    }
    throw error;
  }
}

export function adminForgotPassword(input: {
  email: string;
}): Promise<ApiSuccess<unknown>> {
  return apiFetch("/api/v1/admin/auth/forgot-password", {
    method: "POST",
    json: input,
  });
}

export function adminResetPassword(input: {
  email: string;
  token: string;
  password: string;
  password_confirmation: string;
}): Promise<ApiSuccess<unknown>> {
  return apiFetch("/api/v1/admin/auth/reset-password", {
    method: "POST",
    json: input,
  });
}

export function adminTwoFactorChallenge(input: {
  two_factor_token: string;
  code: string;
}): Promise<ApiSuccess<AdminProfile>> {
  return apiFetch<AdminProfile>("/api/v1/admin/auth/two-factor/challenge", {
    method: "POST",
    json: input,
  });
}

export function adminTwoFactorSetup(): Promise<
  ApiSuccess<{ secret: string; otpauth_uri: string }>
> {
  return apiFetch("/api/v1/admin/auth/two-factor/setup", {
    method: "POST",
    json: {},
  });
}

export function adminTwoFactorConfirm(input: {
  code: string;
}): Promise<ApiSuccess<{ recovery_codes: string[] }>> {
  return apiFetch("/api/v1/admin/auth/two-factor/confirm", {
    method: "POST",
    json: input,
  });
}

export function adminMe(): Promise<ApiSuccess<AdminProfile>> {
  return apiFetch<AdminProfile>("/api/v1/admin/me");
}
