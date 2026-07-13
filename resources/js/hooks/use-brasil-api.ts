import { useEffect, useRef, useState } from 'react';

type BrazilState = {
    id: number;
    sigla: string;
    nome: string;
};

type BrazilCity = {
    nome: string;
    codigo_ibge: string;
};

export function useStates() {
    const [states, setStates] = useState<BrazilState[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetch('/api/brasil/states')
            .then((res) => res.json())
            .then((data: BrazilState[]) => setStates(data))
            .catch(() => setStates([]))
            .finally(() => setLoading(false));
    }, []);

    return { states, loading };
}

export function useCities(uf: string) {
    const [cities, setCities] = useState<BrazilCity[]>([]);
    const [loading, setLoading] = useState(false);
    const fetchIdRef = useRef(0);

    useEffect(() => {
        if (!uf) {
            return;
        }

        const id = ++fetchIdRef.current;

        // eslint-disable-next-line react-hooks/set-state-in-effect -- loading state must be set synchronously to avoid UI flash
        setLoading(true);

        fetch(`/api/brasil/cities/${uf}`)
            .then((res) => res.json())
            .then((data: BrazilCity[]) => {
                if (fetchIdRef.current === id) {
                    setCities(data);
                    setLoading(false);
                }
            })
            .catch(() => {
                if (fetchIdRef.current === id) {
                    setCities([]);
                    setLoading(false);
                }
            });
    }, [uf]);

    const resolvedCities = uf ? cities : [];

    return { cities: resolvedCities, loading };
}
