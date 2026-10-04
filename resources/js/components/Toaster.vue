<script setup lang="ts">
import { useToasts, type Toast } from '@/composables/useToasts';
import { CheckCircle2, Info, TriangleAlert, X, XCircle } from 'lucide-vue-next';

const { toasts, dismissToast } = useToasts();

const styles: Record<
    Toast['type'],
    { icon: typeof Info; accent: string; bar: string }
> = {
    success: {
        icon: CheckCircle2,
        accent: 'text-green-600 dark:text-green-400',
        bar: 'bg-green-600',
    },
    info: {
        icon: Info,
        accent: 'text-sky-600 dark:text-sky-400',
        bar: 'bg-sky-600',
    },
    warning: {
        icon: TriangleAlert,
        accent: 'text-amber-600 dark:text-amber-400',
        bar: 'bg-amber-500',
    },
    error: {
        icon: XCircle,
        accent: 'text-red-600 dark:text-red-400',
        bar: 'bg-red-600',
    },
};
</script>

<template>
    <!-- Flying notifications: slide in, stay a few seconds, fade out -->
    <div
        class="pointer-events-none fixed inset-x-4 top-4 z-[100] flex flex-col items-end gap-2 sm:inset-x-auto sm:right-4 sm:w-96"
        aria-live="polite"
    >
        <TransitionGroup
            enter-active-class="transition duration-300 ease-out motion-reduce:transition-none"
            enter-from-class="translate-x-8 opacity-0"
            leave-active-class="transition duration-300 ease-in motion-reduce:transition-none"
            leave-to-class="-translate-y-2 opacity-0"
            move-class="transition-transform duration-300"
        >
            <div
                v-for="item in toasts"
                :key="item.id"
                :role="item.type === 'error' ? 'alert' : 'status'"
                class="group pointer-events-auto relative w-full overflow-hidden rounded-lg border border-sidebar-border bg-card text-card-foreground shadow-lg"
            >
                <div class="flex items-start gap-3 py-3 pr-2 pl-4">
                    <component
                        :is="styles[item.type].icon"
                        :class="[
                            'mt-0.5 size-5 shrink-0',
                            styles[item.type].accent,
                        ]"
                        aria-hidden="true"
                    />
                    <p class="flex-1 text-sm leading-relaxed">
                        {{ item.message }}
                    </p>
                    <button
                        type="button"
                        class="rounded-md p-1 text-muted-foreground hover:bg-accent hover:text-foreground"
                        aria-label="Dismiss"
                        @click="dismissToast(item.id)"
                    >
                        <X class="size-4" />
                    </button>
                </div>
                <!-- Time left; pauses while hovered, and the toast closes when it runs out -->
                <div
                    :class="[
                        'toast-timer h-1 origin-left group-hover:[animation-play-state:paused]',
                        styles[item.type].bar,
                    ]"
                    :style="{ animationDuration: `${item.duration}ms` }"
                    @animationend="dismissToast(item.id)"
                />
            </div>
        </TransitionGroup>
    </div>
</template>

<style scoped>
.toast-timer {
    animation-name: toast-timer;
    animation-timing-function: linear;
    animation-fill-mode: forwards;
}

@keyframes toast-timer {
    from {
        transform: scaleX(1);
    }
    to {
        transform: scaleX(0);
    }
}
</style>
