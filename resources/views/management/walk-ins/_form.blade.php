@php
    $isModal = $isModal ?? false;
    $formIdPrefix = $formIdPrefix ?? 'walk-in';
    $modalKey = $modalKey ?? null;
@endphp

<form
    method="POST"
    action="{{ route('management.walk-ins.store') }}"
    class="{{ $isModal ? 'space-y-7 p-5 sm:p-6' : 'spa-panel p-6 sm:p-8' }}"
    data-appointment-booking-form
    data-slots-url="{{ route('management.walk-ins.slots') }}"
>
    @csrf
    @if ($modalKey)<input type="hidden" name="_modal" value="{{ $modalKey }}">@endif

    @unless ($isModal)
        <div class="mb-7">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-sage-700">Staff-created appointment</p>
            <h2 class="mt-2 text-xl font-semibold text-cocoa-950">Book a walk-in guest</h2>
            <p class="mt-1 text-sm text-cocoa-500">This appointment will be marked in its notes as a walk-in booking created by you.</p>
        </div>
    @endunless

    <div class="grid gap-6 sm:grid-cols-2">
        <x-form.select :id="$formIdPrefix.'-customer'" name="customer_profile_id" label="Customer" required wrapper-class="sm:col-span-2" hint="Only active customer profiles are available.">
            <option value="">Select a customer</option>
            @foreach ($customers as $customer)
                <option value="{{ $customer->id }}" @selected((string) old('customer_profile_id') === (string) $customer->id)>{{ trim($customer->first_name.' '.$customer->last_name) }}{{ $customer->phone ? ' - '.$customer->phone : '' }}</option>
            @endforeach
        </x-form.select>

        <div class="-mt-3 sm:col-span-2">
            <a href="{{ route('management.customers.create') }}" class="text-sm font-semibold text-sage-700 underline decoration-sage-300 underline-offset-4 hover:text-sage-900">Create a customer profile for a new walk-in guest</a>
        </div>

        <x-form.select :id="$formIdPrefix.'-service'" name="service_id" label="Service" required wrapper-class="sm:col-span-2" hint="Select a spa service to determine the required appointment duration.">
            <option value="">Select a service</option>
            @foreach ($services as $service)
                <option value="{{ $service->id }}" data-duration="{{ $service->duration_minutes }}" @selected((string) old('service_id') === (string) $service->id)>{{ $service->name }} - {{ $service->duration_minutes }} minutes - PHP {{ number_format((float) $service->price, 2) }}</option>
            @endforeach
        </x-form.select>

        <x-form.select :id="$formIdPrefix.'-therapist'" name="therapist_profile_id" label="Therapist" required wrapper-class="sm:col-span-2" hint="Choose a specific therapist.">
            <option value="">Select a therapist</option>
            @foreach ($therapists as $therapist)
                <option value="{{ $therapist->id }}" @selected((string) old('therapist_profile_id') === (string) $therapist->id)>{{ trim($therapist->first_name.' '.$therapist->last_name) }}{{ $therapist->specialty ? ' - '.$therapist->specialty : '' }}</option>
            @endforeach
        </x-form.select>

        <x-form.input :id="$formIdPrefix.'-date'" name="appointment_date" label="Appointment date" type="date" :min="now()->toDateString()" required hint="Defaults to today for walk-ins, but future dates can also be scheduled." />

        <fieldset class="sm:col-span-2" data-slot-picker>
            <legend class="text-sm font-semibold text-cocoa-700">Available times <span class="text-red-700" aria-hidden="true">*</span></legend>
            <input type="hidden" name="appointment_time" value="{{ old('appointment_time') }}" data-selected-slot>
            <p class="mt-1.5 text-xs leading-5 text-cocoa-500">Today and future dates are supported. Past times and conflicting appointments are excluded automatically.</p>
            <div class="mt-3 rounded-2xl border border-dashed border-cream-300 bg-cream-50/70 px-5 py-6 text-center text-sm text-cocoa-600" data-slot-status role="status" aria-live="polite">Select all booking details to view available times.</div>
            <div class="mt-3 hidden grid-cols-2 gap-3 sm:grid-cols-3" data-slot-options></div>
            <div class="mt-3 hidden rounded-2xl border border-dashed border-cream-300 bg-cream-50/70 px-5 py-7 text-center" data-slot-empty>
                <p class="font-semibold text-cocoa-900">No available times for this date.</p>
                <p class="mt-1 text-sm text-cocoa-500">Choose another date or therapist.</p>
            </div>
            @error('appointment_time')<p class="mt-2 text-sm font-medium text-red-700">{{ $message }}</p>@enderror
        </fieldset>

        <x-form.textarea :id="$formIdPrefix.'-notes'" name="notes" label="Notes (optional)" rows="4" maxlength="2000" placeholder="Add preferences or details that will help the therapist prepare." wrapper-class="sm:col-span-2" />
    </div>

    <div class="flex flex-col-reverse gap-3 border-t border-cream-200 pt-6 sm:flex-row sm:justify-end {{ $isModal ? '' : 'mt-7' }}">
        @if ($isModal)
            <x-button type="button" variant="secondary" data-modal-close>Cancel</x-button>
        @else
            <x-button :href="route('management.index')" variant="secondary">Cancel</x-button>
        @endif
        <x-button type="submit" data-booking-submit disabled>Book walk-in</x-button>
    </div>
</form>
