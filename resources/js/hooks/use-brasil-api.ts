import { useEffect, useState } from 'react';

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

    useEffect(() => {
        if (!uf) {
            setCities([]);

            return;
        }

        setLoading(true);
        fetch(`/api/brasil/cities/${uf}`)
            .then((res) => res.json())
            .then((data: BrazilCity[]) => setCities(data))
            .catch(() => setCities([]))
            .finally(() => setLoading(false));
    }, [uf]);

    return { cities, loading };
}
