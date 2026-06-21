@props([
    'id',
    'title' => 'Confirm status change',
])

<x-modal :id="$id" :title="$title" description="Review the impact before continuing." size="sm">
    <form method="POST" action="#" data-confirmation-form class="space-y-6 p-5 sm:p-6">
        @csrf
        @method('PATCH')

        <div class="rounded-2xl border border-gold-300/60 bg-gold-100/60 p-4">
            <p class="font-semibold text-cocoa-950" data-confirmation-heading>Status change</p>
            <p class="mt-2 text-sm leading-6 text-cocoa-600" data-confirmation-message>This record's status will be updated.</p>
        </div>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <x-button type="button" variant="secondary" data-modal-close>Cancel</x-button>
            <x-button type="submit" data-confirmation-submit>Confirm change</x-button>
        </div>
    </form>
</x-modal>
