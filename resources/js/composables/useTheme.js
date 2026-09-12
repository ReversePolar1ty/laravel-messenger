import { usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const applyTheme = (theme) => {
    document.documentElement.dataset.theme = theme === 'light' ? 'light' : 'dark';
};

export function useTheme() {
    const page = usePage();
    const theme = ref(page.props.theme === 'light' ? 'light' : 'dark');
    const saving = ref(false);

    watch(
        () => page.props.theme,
        (value) => {
            const next = value === 'light' ? 'light' : 'dark';
            theme.value = next;
            applyTheme(next);
        },
        { immediate: true },
    );

    const isDark = computed(() => theme.value === 'dark');
    const isLight = computed(() => theme.value === 'light');

    const setTheme = async (next) => {
        const normalized = next === 'light' ? 'light' : 'dark';

        if (normalized === theme.value || saving.value) {
            return;
        }

        const previous = theme.value;
        theme.value = normalized;
        applyTheme(normalized);
        saving.value = true;

        try {
            const response = await window.axios.put(
                route('users.theme.update'),
                { theme: normalized },
                { skipGlobalLoader: true },
            );

            theme.value = response.data.theme === 'light' ? 'light' : 'dark';
            applyTheme(theme.value);
        } catch {
            theme.value = previous;
            applyTheme(previous);
        } finally {
            saving.value = false;
        }
    };

    const toggleTheme = () => setTheme(theme.value === 'dark' ? 'light' : 'dark');

    return {
        theme,
        isDark,
        isLight,
        saving,
        setTheme,
        toggleTheme,
    };
}
