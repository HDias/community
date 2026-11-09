import { Form, Head, setLayoutProps } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type ContributionSetting = {
    id: number;
    monthly_amount: string;
    effective_from: string;
    editable: boolean;
};

type Props = {
    community: { id: number; name: string; slug: string };
    settings: ContributionSetting[];
};

export default function ContributionsSettings({ community, settings }: Props) {
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Contributions',
                href: `/contributions?community=${community.id}`,
            },
            {
                title: 'Settings',
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
                                <th className="px-4 py-2 text-right font-medium">
                                    Actions
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
                                    <td className="px-4 py-2 text-right">
                                        {setting.editable ? (
                                            <Dialog>
                                                <DialogTrigger asChild>
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                    >
                                                        Edit
                                                    </Button>
                                                </DialogTrigger>
                                                <DialogContent>
                                                    <DialogTitle>
                                                        Edit contribution amount
                                                    </DialogTitle>
                                                    <DialogDescription>
                                                        Correct the amount
                                                        effective from{' '}
                                                        {setting.effective_from}
                                                        . Past settings can be
                                                        edited.
                                                    </DialogDescription>

                                                    <Form
                                                        action={`/contributions/settings/${setting.id}?community=${community.id}`}
                                                        method="put"
                                                        options={{
                                                            preserveScroll: true,
                                                        }}
                                                    >
                                                        {({
                                                            errors,
                                                            processing,
                                                        }) => (
                                                            <>
                                                                <div className="grid gap-2">
                                                                    <Label
                                                                        htmlFor={`monthly-amount-${setting.id}`}
                                                                    >
                                                                        Monthly
                                                                        amount *
                                                                    </Label>
                                                                    <Input
                                                                        id={`monthly-amount-${setting.id}`}
                                                                        name="monthly_amount"
                                                                        type="number"
                                                                        step="0.01"
                                                                        min="0.01"
                                                                        defaultValue={
                                                                            setting.monthly_amount
                                                                        }
                                                                        required
                                                                    />
                                                                    <InputError
                                                                        message={
                                                                            errors.monthly_amount
                                                                        }
                                                                    />
                                                                </div>
                                                                <div className="grid gap-2">
                                                                    <Label
                                                                        htmlFor={`effective-from-${setting.id}`}
                                                                    >
                                                                        Effective
                                                                        from *
                                                                    </Label>
                                                                    <Input
                                                                        id={`effective-from-${setting.id}`}
                                                                        name="effective_from"
                                                                        type="date"
                                                                        defaultValue={
                                                                            setting.effective_from
                                                                        }
                                                                        required
                                                                    />
                                                                    <InputError
                                                                        message={
                                                                            errors.effective_from
                                                                        }
                                                                    />
                                                                </div>

                                                                <DialogFooter className="gap-2">
                                                                    <DialogClose
                                                                        asChild
                                                                    >
                                                                        <Button
                                                                            type="button"
                                                                            variant="secondary"
                                                                        >
                                                                            Cancel
                                                                        </Button>
                                                                    </DialogClose>
                                                                    <Button
                                                                        type="submit"
                                                                        disabled={
                                                                            processing
                                                                        }
                                                                    >
                                                                        Save
                                                                        changes
                                                                    </Button>
                                                                </DialogFooter>
                                                            </>
                                                        )}
                                                    </Form>
                                                </DialogContent>
                                            </Dialog>
                                        ) : (
                                            <span className="text-sm text-muted-foreground">
                                                -
                                            </span>
                                        )}
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
