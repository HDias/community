import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

export type ContributionHistoryEntry = {
    id: number;
    amount: string;
    status: 'active' | 'reversed';
    registered_at: string | null;
    registered_by_name: string | null;
    reversed_at: string | null;
    reversed_by_name: string | null;
    reversal_reason: string | null;
};

type Props = {
    history: ContributionHistoryEntry[];
};

function formatMoment(value: string | null): string {
    if (!value) {
        return '-';
    }

    const date = new Date(value);

    return Number.isNaN(date.getTime()) ? '-' : date.toLocaleString('en-GB');
}

export function ContributionHistoryDialog({ history }: Props) {
    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button
                    type="button"
                    size="sm"
                    variant="outline"
                    disabled={history.length === 0}
                >
                    History
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Payment history</DialogTitle>
                <DialogDescription>
                    Registration and reversal events for this payment.
                </DialogDescription>

                {history.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No history recorded.
                    </p>
                ) : (
                    <ol className="space-y-3">
                        {history.map((entry) => (
                            <li
                                key={entry.id}
                                className="rounded-lg border p-3 text-sm"
                            >
                                <div className="flex items-center justify-between gap-2">
                                    <span className="font-medium">
                                        Registered{' '}
                                        {formatMoment(entry.registered_at)}
                                    </span>
                                    <Badge
                                        variant={
                                            entry.status === 'active'
                                                ? 'default'
                                                : 'secondary'
                                        }
                                    >
                                        {entry.status === 'active'
                                            ? 'Active'
                                            : 'Reversed'}
                                    </Badge>
                                </div>
                                <p className="text-muted-foreground">
                                    By {entry.registered_by_name ?? '-'}
                                </p>
                                <p className="text-muted-foreground">
                                    Amount: R$ {entry.amount}
                                </p>

                                {entry.reversed_at && (
                                    <div className="mt-2 space-y-1 border-t pt-2">
                                        <p className="font-medium">
                                            Reversed{' '}
                                            {formatMoment(entry.reversed_at)}
                                        </p>
                                        <p className="text-muted-foreground">
                                            By {entry.reversed_by_name ?? '-'}
                                        </p>
                                        {entry.reversal_reason && (
                                            <p>
                                                Reason: {entry.reversal_reason}
                                            </p>
                                        )}
                                    </div>
                                )}
                            </li>
                        ))}
                    </ol>
                )}
            </DialogContent>
        </Dialog>
    );
}
