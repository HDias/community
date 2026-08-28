import { router, setLayoutProps } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

type ContributionReport = {
    id: number;
    total_members: number;
    total_paid: number;
    total_defaulting: number;
    total_collected: string;
    generated_at: string;
} | null;

type Defaulter = {
    user_id: number;
    name: string;
    months_owed: number;
    last_payment: string | null;
    overdue_3plus: boolean;
};

type Props = {
    community: { id: number; name: string; slug: string };
    referenceMonth: string;
    report: ContributionReport;
    defaulters: Defaulter[];
};

export default function ContributionsReport({
    community,
    referenceMonth,
    defaulters,
}: Props) {
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Contributions Report',
                href: `/contributions/report?community=${community.id}`,
            },
        ],
    });

    const [month, setMonth] = useState(referenceMonth);

    function viewMonth() {
        router.visit(
            `/contributions/report?community=${community.id}&month=${month}`,
        );
    }

    return (
        <div className="flex h-full flex-1 flex-col gap-6 p-6">
            <div className="flex items-end gap-3">
                <div className="grid gap-2">
                    <Label htmlFor="report-month">Month</Label>
                    <Input
                        id="report-month"
                        type="month"
                        value={month}
                        onChange={(e) => setMonth(e.target.value)}
                    />
                </div>
                <Button onClick={viewMonth}>View</Button>
            </div>

            {defaulters.length > 0 ? (
                <table className="w-full text-sm">
                    <thead className="border-b bg-muted/50">
                        <tr>
                            <th className="px-4 py-2 text-left font-medium">
                                Name
                            </th>
                            <th className="px-4 py-2 text-left font-medium">
                                Months owed
                            </th>
                            <th className="px-4 py-2 text-left font-medium">
                                Last payment
                            </th>
                        </tr>
                    </thead>
                    <tbody className="divide-y">
                        {defaulters.map((defaulter) => (
                            <tr
                                key={defaulter.user_id}
                                className={cn(
                                    defaulter.overdue_3plus &&
                                        'text-red-600 dark:text-red-400',
                                )}
                            >
                                <td className="px-4 py-2">{defaulter.name}</td>
                                <td className="px-4 py-2">
                                    {defaulter.months_owed}
                                </td>
                                <td className="px-4 py-2">
                                    {defaulter.last_payment ?? '-'}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            ) : (
                <p className="text-sm text-muted-foreground/70">
                    No defaulters for this month.
                </p>
            )}
        </div>
    );
}
