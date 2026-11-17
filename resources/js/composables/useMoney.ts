import { usePage } from '@inertiajs/vue3';
import { computed, type ComputedRef } from 'vue';

export type UseMoneyReturn = {
    currency: ComputedRef<string>;
    format: (amount: number | string) => string;
};

export function createMoneyFormatter(
    currencyCode: string,
): (amount: number | string) => string {
    const formatter = new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: currencyCode,
    });

    return (amount: number | string): string =>
        formatter.format(Number(amount));
}

export function useMoney(): UseMoneyReturn {
    const page = usePage();
    const currency = computed(() => page.props.finance.currency);

    return { currency, format: createMoneyFormatter(currency.value) };
}
