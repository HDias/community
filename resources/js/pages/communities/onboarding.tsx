import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { LocationFields } from '@/components/location-fields';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

type Props = {
    canCreate: boolean;
};

export default function Onboarding({ canCreate }: Props) {
    const [showForm, setShowForm] = useState(false);

    return (
        <>
            <Head title="Welcome" />
            <div className="flex min-h-[60vh] flex-col items-center justify-center p-8">
                {!showForm ? (
                    <>
                        <h1 className="text-3xl font-bold">
                            Welcome to the Community
                        </h1>
                        <p className="mt-4 text-lg text-muted-foreground">
                            You don't belong to any community yet.
                        </p>
                        {canCreate ? (
                            <>
                                <p className="mt-2 text-muted-foreground">
                                    Create a new community to get started.
                                </p>
                                <Button
                                    className="mt-6"
                                    onClick={() => setShowForm(true)}
                                >
                                    + Create community
                                </Button>
                            </>
                        ) : (
                            <p className="mt-2 text-muted-foreground">
                                Please wait for an administrator to add you to a
                                community.
                            </p>
                        )}
                    </>
                ) : (
                    <div className="w-full max-w-md">
                        <h2 className="text-2xl font-bold">
                            Create a community
                        </h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            You'll become the first member and President.
                        </p>
                        <Form
                            action="/communities"
                            method="post"
                            className="mt-6 space-y-4"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">
                                            Community name *
                                        </Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            required
                                            placeholder="Quilombo São Roque"
                                            autoFocus
                                        />
                                        <InputError message={errors.name} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="description">
                                            Description
                                        </Label>
                                        <Input
                                            id="description"
                                            name="description"
                                            placeholder="Brief description"
                                        />
                                        <InputError
                                            message={errors.description}
                                        />
                                    </div>
                                    <LocationFields />
                                    <div className="flex items-center gap-3 pt-2">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            onClick={() => setShowForm(false)}
                                        >
                                            Cancel
                                        </Button>
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            {processing && <Spinner />}
                                            Create community
                                        </Button>
                                    </div>
                                </>
                            )}
                        </Form>
                    </div>
                )}
            </div>
        </>
    );
}
