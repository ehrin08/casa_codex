@php
    $fieldValue = fn (string $name, mixed $value = null) => old($name, $value);
    $startsAt = $promotion->starts_at?->format('Y-m-d\TH:i');
    $endsAt = $promotion->ends_at?->format('Y-m-d\TH:i');
@endphp

<form method="POST" action="{{ $promotion->exists ? route('management.promotions.update', $promotion) : route('management.promotions.store') }}" class="mx-auto max-w-4xl space-y-8">
    @csrf
    @if ($promotion->exists) @method('PUT') @endif

    <section class="spa-panel space-y-6 p-6 sm:p-8">
        <div>
            <h2 class="spa-section-title">Promotion details</h2>
            <p class="mt-1 text-sm leading-6 text-cocoa-500">Use a clear internal title and describe the intended offer.</p>
        </div>
        <div class="grid gap-6 sm:grid-cols-2">
            <x-form.input name="title" label="Rule title" :value="$promotion->title" required wrapper-class="sm:col-span-2" />
            <x-form.textarea name="description" label="Description" :value="$promotion->description" rows="4" maxlength="2000" wrapper-class="sm:col-span-2" />
            <x-form.select name="status" label="Status" required hint="Draft rules remain editable; only active rules can be considered by the future promotion engine.">
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected($fieldValue('status', $promotion->status ?: \App\Models\Promotion::STATUS_DRAFT) === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </x-form.select>
        </div>
    </section>

    <section class="spa-panel space-y-6 p-6 sm:p-8">
        <div>
            <h2 class="spa-section-title">Discount settings</h2>
            <p class="mt-1 text-sm leading-6 text-cocoa-500">Choose a percentage reduction or a fixed amount in Philippine pesos.</p>
        </div>
        <div class="grid gap-6 sm:grid-cols-2">
            <x-form.select name="discount_type" label="Discount type" required>
                @foreach ($discountTypes as $discountType)
                    <option value="{{ $discountType }}" @selected($fieldValue('discount_type', $promotion->discount_type ?: \App\Models\Promotion::DISCOUNT_TYPE_PERCENTAGE) === $discountType)>{{ $discountType === 'fixed' ? 'Fixed amount' : 'Percentage' }}</option>
                @endforeach
            </x-form.select>
            <x-form.input name="discount_value" label="Discount value" type="number" :value="$promotion->discount_value" min="0.01" step="0.01" required hint="Percentage discounts cannot exceed 100. Fixed discounts accept normal positive money values." />
        </div>
    </section>

    <section class="spa-panel space-y-6 p-6 sm:p-8">
        <div>
            <h2 class="spa-section-title">RFM targeting</h2>
            <p class="mt-1 text-sm leading-6 text-cocoa-500">Choose a segment, one or more minimum scores, or both. When multiple criteria are set, a customer must satisfy every criterion. A blank score means no minimum for that dimension.</p>
        </div>
        <div class="grid gap-6 sm:grid-cols-2">
            <x-form.select name="rfm_segment_label" label="Target RFM segment" hint="Optional when at least one score threshold is set." wrapper-class="sm:col-span-2">
                <option value="">Any segment</option>
                @foreach ($segments as $segment)
                    <option value="{{ $segment }}" @selected($fieldValue('rfm_segment_label', $promotion->rfm_segment_label) === $segment)>{{ $segment }}</option>
                @endforeach
            </x-form.select>
            <x-form.input name="rule_min_recency_score" label="Minimum recency score" type="number" :value="$promotion->rule_min_recency_score" min="1" max="5" hint="1 is least recent; 5 is most recent." />
            <x-form.input name="rule_min_frequency_score" label="Minimum frequency score" type="number" :value="$promotion->rule_min_frequency_score" min="1" max="5" hint="1 is least frequent; 5 is most frequent." />
            <x-form.input name="rule_min_monetary_score" label="Minimum monetary score" type="number" :value="$promotion->rule_min_monetary_score" min="1" max="5" hint="1 is lowest spend; 5 is highest spend." />
        </div>
    </section>

    <section class="spa-panel space-y-6 p-6 sm:p-8">
        <div>
            <h2 class="spa-section-title">Active date window</h2>
            <p class="mt-1 text-sm leading-6 text-cocoa-500">Dates limit when an active rule can be considered. Leave either boundary blank for an open-ended window.</p>
        </div>
        <div class="grid gap-6 sm:grid-cols-2">
            <x-form.input name="starts_at" label="Starts at" type="datetime-local" :value="$startsAt" />
            <x-form.input name="ends_at" label="Ends at" type="datetime-local" :value="$endsAt" />
        </div>
    </section>

    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <x-button :href="route('management.promotions.index')" variant="secondary">Cancel</x-button>
        <x-button type="submit">{{ $promotion->exists ? 'Save Promotion' : 'Create Promotion' }}</x-button>
    </div>
</form>
