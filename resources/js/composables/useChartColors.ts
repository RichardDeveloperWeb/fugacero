import { useAppearance } from '@/composables/useAppearance';
import {
    computed,
    nextTick,
    onMounted,
    ref,
    watch,
    type ComputedRef,
} from 'vue';

export type UseChartColorsReturn = {
    palette: ComputedRef<string[]>;
    text: ComputedRef<string>;
    grid: ComputedRef<string>;
    border: ComputedRef<string>;
};

const fallbackPalette = ['#f97316', '#3b82f6', '#22c55e', '#a855f7', '#eab308'];

const paletteVariables = [
    '--chart-1',
    '--chart-2',
    '--chart-3',
    '--chart-4',
    '--chart-5',
];

function canvasColor(value: string): string {
    if (typeof document === 'undefined' || value === '') {
        return value;
    }

    const context = document.createElement('canvas').getContext('2d');

    if (!context) {
        return value;
    }

    context.fillStyle = '#000000';
    context.fillStyle = value;

    return context.fillStyle;
}

export function useChartColors(): UseChartColorsReturn {
    const { resolvedAppearance } = useAppearance();
    const palette = ref<string[]>(fallbackPalette);
    const text = ref('#71717a');
    const grid = ref('#e4e4e7');
    const border = ref('#ffffff');

    function refresh(): void {
        if (typeof window === 'undefined') {
            return;
        }

        const style = getComputedStyle(document.documentElement);
        const read = (name: string, fallback: string): string =>
            canvasColor(style.getPropertyValue(name).trim() || fallback);

        palette.value = paletteVariables.map((name, index) =>
            read(name, fallbackPalette[index] ?? '#71717a'),
        );
        text.value = read('--muted-foreground', '#71717a');
        grid.value = read('--border', '#e4e4e7');
        border.value = read('--card', '#ffffff');
    }

    onMounted(refresh);

    watch(
        resolvedAppearance,
        async () => {
            await nextTick();
            refresh();
        },
        { flush: 'post' },
    );

    return {
        palette: computed(() => palette.value),
        text: computed(() => text.value),
        grid: computed(() => grid.value),
        border: computed(() => border.value),
    };
}
