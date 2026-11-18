<script setup lang="ts">
import { Shapes } from '@lucide/vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';

withDefaults(
    defineProps<{
        title: string;
        description?: string;
        hasData?: boolean;
        emptyTitle?: string;
        emptyDescription?: string;
    }>(),
    {
        description: undefined,
        hasData: true,
        emptyTitle: 'Sin datos',
        emptyDescription: 'Todavía no hay información para mostrar.',
    },
);
</script>

<template>
    <Card class="min-w-0">
        <CardHeader>
            <CardTitle>{{ title }}</CardTitle>
            <CardDescription v-if="description">
                {{ description }}
            </CardDescription>
        </CardHeader>
        <CardContent class="min-w-0">
            <Empty v-if="!hasData">
                <EmptyHeader>
                    <EmptyMedia variant="icon"><Shapes /></EmptyMedia>
                    <EmptyTitle>{{ emptyTitle }}</EmptyTitle>
                    <EmptyDescription>
                        {{ emptyDescription }}
                    </EmptyDescription>
                </EmptyHeader>
            </Empty>

            <div v-else class="relative h-64 w-full min-w-0">
                <slot />
            </div>

            <div v-if="$slots.summary" class="mt-4">
                <slot name="summary" />
            </div>
        </CardContent>
    </Card>
</template>
