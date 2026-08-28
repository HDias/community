import { Form, Head, setLayoutProps } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type ContributionSetting = {
    id: number;
    monthly_amount: string;
    effective_from: string;
};

type Props = {
    community: { id: number; name: string; slug: string };
    settings: ContributionSetting[];
};

export default function ContributionsSettings({ community, settings }: Props) {
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Contribution Settings',
                href: `/contributions/settings?community=${community.id}`,
            },
        ],
    });

    return (
        <>
            <Head title={`Contribution Settings - ${community.name}`} />
            <div className="mx-auto w-full max-w-2xl px-6 py-8">
                <h1 className="mb-6 text-lg font-semibold">
                    Contribution Settings
                </h1>

                <Form
                    action={`/contributions/settings?community=${community.id}`}
                    method="post"
                    className="space-y-6"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="monthly_amount">
                                        Monthly amount *
                                    </Label>
                                    <Input
                                        id="monthly_amount"
                                        name="monthly_amount"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        required
                                    />
                                    <InputError
                                        message={errors.monthly_amount}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="effective_from">
                                        Effective from *
                                    </Label>
                                    <Input
                                        id="effective_from"
                                        name="effective_from"
                                        type="date"
                                        required
                                    />
                                    <InputError
                                        message={errors.effective_from}
                                    />
                                </div>
                            </div>

                            <div className="flex justify-end">
                                <Button type="submit" disabled={processing}>
                                    Update amount
                                </Button>
                            </div>
                        </>
                    )}
                </Form>

                <h2 className="text-md mt-10 mb-4 font-medium">History</h2>
                {settings.length > 0 ? (
                    <table className="w-full text-sm">
                        <thead className="border-b bg-muted/50">
                            <tr>
                                <th className="px-4 py-2 text-left font-medium">
                                    Effective from
                                </th>
                                <th className="px-4 py-2 text-left font-medium">
                                    Monthly amount
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {settings.map((setting) => (
                                <tr key={setting.id}>
                                    <td className="px-4 py-2">
                                        {setting.effective_from}
                                    </td>
                                    <td className="px-4 py-2">
                                        {setting.monthly_amount}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                ) : (
                    <p className="text-sm text-muted-foreground/70">
                        No settings recorded yet.
                    </p>
                )}
            </div>
        </>
    );
}
