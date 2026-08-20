export type Deferred<T> = {
    promise: Promise<T>;
    resolve: (value: T) => void;
    reject: (reason?: unknown) => void;
};

/** A promise whose settlement is controlled by the test. */
export function deferred<T>(): Deferred<T> {
    let resolve!: (value: T) => void;
    let reject!: (reason?: unknown) => void;

    const promise = new Promise<T>((res, rej) => {
        resolve = res;
        reject = rej;
    });

    return { promise, resolve, reject };
}

/** The minimum of `Response` that the app's `fetch` callers actually touch. */
export function jsonResponse(data: unknown): Response {
    return { json: () => Promise.resolve(data) } as unknown as Response;
}

/** Drains the microtask queue so chained `.then()` handlers all run. */
export function flush(): Promise<void> {
    return new Promise((resolve) => setTimeout(resolve, 0));
}
