"use client";

import { createContext, useContext, type ReactNode } from "react";

import { STORE_NAME } from "@/lib/storefront/ui";

const StoreNameContext = createContext(STORE_NAME);

export function StoreNameProvider({
  storeName,
  children,
}: {
  storeName: string;
  children: ReactNode;
}) {
  return (
    <StoreNameContext.Provider value={storeName || STORE_NAME}>
      {children}
    </StoreNameContext.Provider>
  );
}

export function useStoreName(): string {
  return useContext(StoreNameContext);
}
