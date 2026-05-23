import { onBeforeUnmount, watch } from 'vue';

export function useAutoFilter(form, fields, routeName, options = {}) {
    let timeout = null;
    const delay = options.delay ?? 350;

    const stop = watch(
        () => fields.map((field) => form[field]),
        () => {
            if (timeout) {
                window.clearTimeout(timeout);
            }

            timeout = window.setTimeout(() => {
                form.get(route(routeName), {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                });
            }, delay);
        },
    );

    onBeforeUnmount(() => {
        stop();

        if (timeout) {
            window.clearTimeout(timeout);
        }
    });
}
