import type { ReactNode } from 'react';
import { useEffect } from 'react';
import { vi } from 'vitest';

type PageProps = Record<string, unknown>;

type Page = {
    url: string;
    component: string;
    props: PageProps;
    version: string | null;
};

type Href = string | { url: string; method?: string };

type FormRenderProps = {
    errors: Record<string, string>;
    processing: boolean;
};

type Submission = {
    action: string;
    method: string;
    data: Record<string, string>;
};

const initialPage = (): Page => ({
    url: '/',
    component: '',
    props: {},
    version: null,
});

let page: Page = initialPage();
let errors: Record<string, string> = {};

/** Every `Form` submit intercepted since the last reset, in order. */
export const submissions: Submission[] = [];

export const router = {
    visit: vi.fn(),
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
    reload: vi.fn(),
    cancelAll: vi.fn(),
};

export const setLayoutProps = vi.fn();
export const createInertiaApp = vi.fn();

/** Sets the page the mocked `usePage` reports. */
export function setPage(next: Partial<Page>): void {
    page = { ...page, ...next };
}

/** Drives the `errors` argument of `Form`'s render prop. */
export function setFormErrors(next: Record<string, string>): void {
    errors = next;
}

export function resetInertia(): void {
    page = initialPage();
    errors = {};
    submissions.length = 0;
    document.title = '';
    Object.values(router).forEach((fn) => fn.mockReset());
    setLayoutProps.mockReset();
    createInertiaApp.mockReset();
}

function hrefToString(href: Href): string {
    return typeof href === 'string' ? href : href.url;
}

/**
 * Replaces `@inertiajs/react` wholesale. Every export the app imports has to be
 * present here, because `vi.mock` with a factory does not fall through to the
 * real module and a missing name fails at import time.
 */
export function createInertiaMock() {
    /** Sets `document.title`, which is the observable half of Inertia's `Head`. */
    function Head({ title }: { title?: string }): null {
        useEffect(() => {
            if (title !== undefined) {
                document.title = title;
            }
        }, [title]);

        return null;
    }

    function Link({
        href,
        children,
        ...rest
    }: {
        href: Href;
        children?: ReactNode;
    }) {
        return (
            <a href={hrefToString(href)} {...rest}>
                {children}
            </a>
        );
    }

    function Form({
        action,
        method = 'get',
        children,
        ...rest
    }: {
        action: string;
        method?: string;
        children: ReactNode | ((props: FormRenderProps) => ReactNode);
    }) {
        return (
            <form
                onSubmit={(event) => {
                    event.preventDefault();

                    const entries = new FormData(event.currentTarget).entries();
                    const data: Record<string, string> = {};

                    for (const [key, value] of entries) {
                        data[key] = String(value);
                    }

                    submissions.push({ action, method, data });
                }}
                {...rest}
            >
                {typeof children === 'function'
                    ? children({ errors, processing: false })
                    : children}
            </form>
        );
    }

    return {
        Head,
        Link,
        Form,
        router,
        setLayoutProps,
        createInertiaApp,
        usePage: () => page,
        useForm: () => ({
            data: {},
            setData: vi.fn(),
            post: vi.fn(),
            put: vi.fn(),
            patch: vi.fn(),
            delete: vi.fn(),
            errors,
            processing: false,
            reset: vi.fn(),
        }),
    };
}
