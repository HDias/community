import { useCallback, useEffect, useRef, useState } from 'react';
import { Input } from '@/components/ui/input';

type Option = {
    id: number;
    name: string;
    email?: string;
};

type Props = {
    endpoint: string;
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
};

export function SearchSelect({ endpoint, value, onChange, placeholder = 'Search...' }: Props) {
    const [query, setQuery] = useState('');
    const [options, setOptions] = useState<Option[]>([]);
    const [isOpen, setIsOpen] = useState(false);
    const [loading, setLoading] = useState(false);
    const [selectedLabel, setSelectedLabel] = useState('');
    const containerRef = useRef<HTMLDivElement>(null);
    const debounceRef = useRef<ReturnType<typeof setTimeout>>(undefined);

    const fetchOptions = useCallback(
        (search: string) => {
            if (search.length < 1) {
                setOptions([]);
                return;
            }

            setLoading(true);
            fetch(`${endpoint}?search=${encodeURIComponent(search)}`, {
                headers: { Accept: 'application/json' },
            })
                .then((res) => res.json())
                .then((data: Option[]) => {
                    setOptions(data);
                    setLoading(false);
                })
                .catch(() => setLoading(false));
        },
        [endpoint],
    );

    function handleInputChange(e: React.ChangeEvent<HTMLInputElement>) {
        const val = e.target.value;
        setQuery(val);
        setIsOpen(true);

        if (value) {
            onChange('');
            setSelectedLabel('');
        }

        if (debounceRef.current) {
            clearTimeout(debounceRef.current);
        }

        debounceRef.current = setTimeout(() => {
            fetchOptions(val);
        }, 300);
    }

    function handleSelect(option: Option) {
        onChange(String(option.id));
        setSelectedLabel(option.name);
        setQuery(option.name);
        setIsOpen(false);
        setOptions([]);
    }

    useEffect(() => {
        if (!value) {
            setQuery('');
            setSelectedLabel('');
        }
    }, [value]);

    useEffect(() => {
        function handleClickOutside(e: MouseEvent) {
            if (containerRef.current && !containerRef.current.contains(e.target as Node)) {
                setIsOpen(false);
            }
        }

        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    return (
        <div ref={containerRef} className="relative">
            <Input
                type="text"
                value={query}
                onChange={handleInputChange}
                onFocus={() => query.length >= 1 && setIsOpen(true)}
                placeholder={placeholder}
                autoComplete="off"
            />
            {isOpen && (query.length >= 1) && (
                <div className="absolute z-50 mt-1 max-h-48 w-full overflow-y-auto rounded-md border bg-popover shadow-md">
                    {loading && (
                        <div className="px-3 py-2 text-sm text-muted-foreground">
                            Searching...
                        </div>
                    )}
                    {!loading && options.length === 0 && (
                        <div className="px-3 py-2 text-sm text-muted-foreground">
                            No results found.
                        </div>
                    )}
                    {!loading &&
                        options.map((option) => (
                            <button
                                key={option.id}
                                type="button"
                                className="flex w-full flex-col px-3 py-2 text-left text-sm hover:bg-accent"
                                onClick={() => handleSelect(option)}
                            >
                                <span className="font-medium">{option.name}</span>
                                {option.email && (
                                    <span className="text-xs text-muted-foreground">
                                        {option.email}
                                    </span>
                                )}
                            </button>
                        ))}
                </div>
            )}
        </div>
    );
}
