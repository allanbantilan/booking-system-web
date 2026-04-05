import axios from "axios";
import { reactive, toRefs } from "vue";

const apiBaseUrl = import.meta.env.VITE_API_URL || "";

const api = axios.create({
    baseURL: apiBaseUrl,
    withCredentials: true,
    timeout: 15000,
    headers: {
        "X-Requested-With": "XMLHttpRequest",
    },
});

type PaymentData = {
    id: number;
    provider: string;
    status: string;
    amount: number;
    currency: string;
    checkout_id: string | null;
    checkout_url: string | null;
    reference: string | null;
    reservation: {
        id: number;
        status: string;
        quantity: number;
        nights: number;
        total_price: number;
        booking: {
            id: number;
            title: string;
            event_date: string;
            booking_type: string;
        } | null;
    } | null;
};

type ApiResponse<T> = {
    data: T;
    message: string;
    errors: Record<string, string[]> | Record<string, unknown>;
};

export const usePayMayaPayments = () => {
    const state = reactive({
        loading: false,
        error: null as string | null,
        data: null as PaymentData | null,
    });

    const createCheckout = async (payload: { booking_id: number; quantity: number; nights: number }) => {
        state.loading = true;
        state.error = null;
        try {
            const response = await api.post<ApiResponse<PaymentData>>(
                "/api/payments/paymaya/checkout",
                payload,
            );
            state.data = response.data.data;
            return response.data.data;
        } catch (error: any) {
            state.error = error?.response?.data?.message || "Unable to create checkout.";
            throw error;
        } finally {
            state.loading = false;
        }
    };

    const fetchCheckoutStatus = async (checkoutId: string) => {
        state.loading = true;
        state.error = null;
        try {
            const response = await api.get<ApiResponse<PaymentData>>(
                `/api/payments/paymaya/checkout/${checkoutId}`,
            );
            state.data = response.data.data;
            return response.data.data;
        } catch (error: any) {
            state.error = error?.response?.data?.message || "Unable to fetch status.";
            throw error;
        } finally {
            state.loading = false;
        }
    };

    return {
        ...toRefs(state),
        createCheckout,
        fetchCheckoutStatus,
    };
};
