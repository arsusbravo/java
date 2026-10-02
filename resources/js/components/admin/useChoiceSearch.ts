import { type Choice } from '@/types/admin';
import { ref, watch, type Ref } from 'vue';

/**
 * Debounced server search for fields whose choices are too many to send
 * with the form (see App\Admin\Field::isRemote).
 */
export function useChoiceSearch(
    url: Ref<string | undefined>,
    term: Ref<string>,
) {
    const results = ref<Choice[]>([]);
    const loading = ref(false);
    let timer: ReturnType<typeof setTimeout> | undefined;
    let controller: AbortController | undefined;

    const run = async () => {
        if (!url.value) return;
        controller?.abort();
        controller = new AbortController();
        loading.value = true;
        try {
            const response = await fetch(
                `${url.value}?search=${encodeURIComponent(term.value)}`,
                {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    signal: controller.signal,
                },
            );
            if (response.ok) results.value = await response.json();
        } catch (error) {
            if ((error as Error).name !== 'AbortError') throw error;
        } finally {
            loading.value = false;
        }
    };

    watch(term, () => {
        clearTimeout(timer);
        timer = setTimeout(run, 250);
    });

    return { results, loading, search: run };
}
