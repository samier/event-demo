import { reactive, ref } from 'vue';
import type {
    EventFilters,
    EventResource,
    FeedMeta,
    WhenFilter,
} from '@/types/events';

/**
 * Shared data layer for both visual pages.
 *
 * Owns the filter state, talks to /events/feed, and exposes paginated results with
 * "load more" semantics. Keeping this in one composable means Visual 1 and Visual 2
 * can look completely different while sharing identical filtering + fetching logic.
 */
export function useEventFeed(
    initialWhen: WhenFilter = 'upcoming',
    perPage = 24,
) {
    const filters = reactive<EventFilters>({
        q: '',
        city: '',
        type: '',
        from: '',
        to: '',
        when: initialWhen,
    });

    const events = ref<EventResource[]>([]);
    const meta = ref<FeedMeta | null>(null);
    const page = ref(0);
    const loading = ref(false);
    const loaded = ref(false);

    let requestId = 0;

    function buildParams(nextPage: number): URLSearchParams {
        const params = new URLSearchParams({
            page: String(nextPage),
            per_page: String(perPage),
            when: filters.when,
        });

        if (filters.q) {
            params.set('q', filters.q);
        }

        if (filters.city) {
            params.set('city', filters.city);
        }

        if (filters.type) {
            params.set('type', filters.type);
        }

        if (filters.from) {
            params.set('from', filters.from);
        }

        if (filters.to) {
            params.set('to', filters.to);
        }

        return params;
    }

    async function loadMore(): Promise<void> {
        if (loading.value) {
            return;
        }

        if (meta.value && page.value >= meta.value.last_page) {
            return;
        }

        loading.value = true;
        const id = ++requestId;

        try {
            const res = await fetch(
                `/events/feed?${buildParams(page.value + 1)}`,
                {
                    headers: { Accept: 'application/json' },
                },
            );
            const payload = await res.json();

            // Ignore responses from filters that have since changed.
            if (id !== requestId) {
                return;
            }

            events.value.push(...(payload.data as EventResource[]));
            meta.value = payload.meta as FeedMeta;
            page.value = payload.meta.current_page;
            loaded.value = true;
        } finally {
            if (id === requestId) {
                loading.value = false;
            }
        }
    }

    function applyFilters(): Promise<void> {
        events.value = [];
        meta.value = null;
        page.value = 0;
        loaded.value = false;

        return loadMore();
    }

    function resetFilters(): Promise<void> {
        filters.q = '';
        filters.city = '';
        filters.type = '';
        filters.from = '';
        filters.to = '';
        filters.when = initialWhen;

        return applyFilters();
    }

    const hasMore = () => !meta.value || page.value < meta.value.last_page;

    return {
        filters,
        events,
        meta,
        loading,
        loaded,
        loadMore,
        applyFilters,
        resetFilters,
        hasMore,
    };
}
