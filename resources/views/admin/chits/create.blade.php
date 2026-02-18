@extends('layouts.app-custom')

@section('title', __('Create Chit'))

@section('header', __('Create Chit'))

@php
    $chitsCssVersion = file_exists(public_path('css/chits.css'))
        ? filemtime(public_path('css/chits.css'))
        : time();

    $selectedPlan = old('chit_name', array_key_first($planOptions));
    if (! array_key_exists($selectedPlan, $planOptions)) {
        $selectedPlan = array_key_first($planOptions);
    }

    $selectedType = old('chit_type', 'auction');
    $durationPreview = max((int) old('duration_months', 20), 1);
    $totalAmountPreview = $planOptions[$selectedPlan]['amount'] ?? 0;
    $monthlyAmountPreview = (int) round((int) $totalAmountPreview / $durationPreview);
    $selectedMemberSlotsCount = array_sum($selectedMembers);
@endphp

@push('styles')
    <link href="{{ asset('css/chits.css') }}?v={{ $chitsCssVersion }}" rel="stylesheet">
@endpush

@section('content')
    <section class="section chit-create-page">
        <div class="row g-3">
            <div class="col-12">
                <div class="card chit-rules-card">
                    <div class="card-body py-3">
                        <h6 class="mb-2">Chit Creation Rules</h6>
                        <ul class="mb-0">
                            <li>Select one chit plan and type first.</li>
                            <li>Total slots required per chit: <strong>{{ $memberLimit }}</strong>.</li>
                            <li>Maximum repeats per member: <strong>{{ $maxRepeatPerMember }}</strong>.</li>
                            <li>Monthly amount is auto-calculated from total value / duration months.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card chit-step-indicator-card">
                    <div class="card-body py-3">
                        <div class="chit-step-indicator">
                            <div id="stepIndicator1" class="step-chip active">
                                <span class="step-chip-number">1</span>
                                <span class="step-chip-label">Chit Details</span>
                            </div>
                            <div class="step-divider"></div>
                            <div id="stepIndicator2" class="step-chip">
                                <span class="step-chip-number">2</span>
                                <span class="step-chip-label">Assign Members</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger mt-3" role="alert">
                <strong>Unable to create chit.</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="chitCreateForm" method="POST" action="{{ route('admin.chits.store') }}" class="mt-3">
            @csrf
            <input
                type="hidden"
                name="selected_members"
                id="selectedMembersInput"
                value="{{ old('selected_members', json_encode($selectedMembers)) }}"
            >

            <div id="slotValidationAlert" class="alert alert-danger d-none" role="alert">
                You must assign exactly {{ $memberLimit }} member slots before creating the chit.
            </div>

            <div id="chitDetailsStep" class="card chit-step-card">
                <div class="card-body">
                    <h5 class="card-title">Step 1: Chit Details</h5>
                    <div id="stepOneSavedAlert" class="alert alert-success d-none" role="alert">
                        Step 1 details saved. You can continue later from this page.
                    </div>
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <label for="chitName" class="form-label">Chit Name</label>
                            <select id="chitName" name="chit_name" class="form-select" required>
                                @foreach ($planOptions as $planKey => $plan)
                                    <option
                                        value="{{ $planKey }}"
                                        data-amount="{{ $plan['amount'] }}"
                                        @selected($selectedPlan === $planKey)
                                    >
                                        {{ $plan['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-6">
                            <label class="form-label d-block">Chit Type</label>
                            <div class="btn-group w-100" role="group" aria-label="Chit type">
                                <input
                                    type="radio"
                                    class="btn-check"
                                    name="chit_type"
                                    id="typeAuction"
                                    value="auction"
                                    autocomplete="off"
                                    @checked($selectedType === 'auction')
                                    required
                                >
                                <label class="btn btn-outline-primary" for="typeAuction">Auction</label>

                                <input
                                    type="radio"
                                    class="btn-check"
                                    name="chit_type"
                                    id="typeFixed"
                                    value="fixed"
                                    autocomplete="off"
                                    @checked($selectedType === 'fixed')
                                    required
                                >
                                <label class="btn btn-outline-primary" for="typeFixed">Fixed</label>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <label for="totalValue" class="form-label">Total Chit Value</label>
                            <input id="totalValue" type="text" class="form-control" value="{{ number_format($totalAmountPreview) }}" readonly>
                        </div>

                        <div class="col-lg-4">
                            <label for="durationMonths" class="form-label">Duration (Months)</label>
                            <input
                                id="durationMonths"
                                type="number"
                                name="duration_months"
                                class="form-control"
                                min="1"
                                max="240"
                                value="{{ old('duration_months', 20) }}"
                                required
                            >
                        </div>

                        <div class="col-lg-4">
                            <label for="monthlyAmount" class="form-label">Monthly Amount</label>
                            <input id="monthlyAmount" type="text" class="form-control" value="{{ number_format($monthlyAmountPreview) }}" readonly>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between flex-wrap gap-2 mt-4">
                        <a href="{{ route('admin.chits.index') }}" class="btn btn-outline-secondary">
                            Back To Chits
                        </a>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" id="saveDetailsStep" class="btn btn-light border">
                                Save Details
                            </button>
                        <button type="button" id="goToMembersStep" class="btn btn-primary">
                                Save & Continue
                        </button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="memberSelectionStep" class="card chit-step-card d-none mt-3">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <h5 class="card-title mb-0">Step 2: Add Members</h5>
                        <div class="slot-counter">
                            Slots Filled: <span id="slotCount">{{ $selectedMemberSlotsCount }}</span>/{{ $memberLimit }}
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-8">
                            <label for="memberSearch" class="form-label mb-1">Search Members</label>
                            <input
                                id="memberSearch"
                                type="text"
                                class="form-control"
                                placeholder="Search by name, email, or mobile number..."
                            >
                        </div>

                        <div class="col-md-4">
                            <label for="memberGovtFilter" class="form-label mb-1">Govt ID Filter</label>
                            <select id="memberGovtFilter" class="form-select">
                                <option value="all">All Members</option>
                                <option value="uploaded">Govt ID Uploaded</option>
                                <option value="missing">Govt ID Missing</option>
                            </select>
                        </div>
                    </div>

                    <p class="small text-muted mb-3">
                        Tip: single click on a member card to add 1 slot, double click to remove 1 slot.
                    </p>

                    <div class="row g-3" id="memberPickGrid">
                        @forelse ($members as $member)
                            @php
                                $slotCount = (int) ($selectedMembers[$member->id] ?? 0);
                                $searchText = strtolower(trim($member->name.' '.$member->email.' '.$member->mobile_number));
                            @endphp
                            <div
                                class="col-12 col-md-6 col-xl-4 member-pick-col"
                                data-search="{{ $searchText }}"
                                data-govt="{{ $member->government_id_path ? 'uploaded' : 'missing' }}"
                            >
                                <article class="card member-pick-card member-pick-clickable" data-member-id="{{ $member->id }}">
                                    <div class="card-body">
                                        <div class="d-flex align-items-start gap-3">
                                            <img src="{{ $member->profile_image_url }}" alt="{{ $member->name }}" class="member-pick-avatar">
                                            <div class="flex-grow-1">
                                                <h6 class="member-pick-name mb-1">{{ $member->name }}</h6>
                                                <p class="member-pick-meta mb-1">{{ $member->email ?: 'No email' }}</p>
                                                <p class="member-pick-meta mb-0">{{ $member->mobile_number ?: 'No mobile' }}</p>
                                            </div>
                                        </div>

                                        <div class="d-flex align-items-center justify-content-between mt-3">
                                            <span class="slot-badge">
                                                Count: <strong class="member-slot-count">{{ $slotCount }}</strong>
                                            </span>
                                            <span class="member-action-hint text-muted">Click actions enabled</span>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="alert alert-warning mb-0">
                                    No members found. Add members first, then create a chit.
                                </div>
                            </div>
                        @endforelse
                    </div>

                    <div class="d-flex flex-wrap justify-content-between gap-2 mt-4">
                        <button type="button" id="backToDetailsStep" class="btn btn-outline-secondary">
                            Back To Chit Details
                        </button>
                        <button type="submit" class="btn btn-primary">
                            Create Chit
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const memberLimit = {{ $memberLimit }};
            const maxRepeatPerMember = {{ $maxRepeatPerMember }};
            const planAmounts = @json(collect($planOptions)->mapWithKeys(static fn ($plan, $key) => [$key => (int) $plan['amount']])->all());

            const form = document.getElementById('chitCreateForm');
            const stepOnePane = document.getElementById('chitDetailsStep');
            const stepTwoPane = document.getElementById('memberSelectionStep');
            const stepIndicator1 = document.getElementById('stepIndicator1');
            const stepIndicator2 = document.getElementById('stepIndicator2');
            const nextButton = document.getElementById('goToMembersStep');
            const backButton = document.getElementById('backToDetailsStep');
            const saveDetailsButton = document.getElementById('saveDetailsStep');
            const stepOneSavedAlert = document.getElementById('stepOneSavedAlert');

            const chitNameInput = document.getElementById('chitName');
            const durationInput = document.getElementById('durationMonths');
            const totalValueInput = document.getElementById('totalValue');
            const monthlyAmountInput = document.getElementById('monthlyAmount');

            const selectedMembersInput = document.getElementById('selectedMembersInput');
            const slotCountNode = document.getElementById('slotCount');
            const slotValidationAlert = document.getElementById('slotValidationAlert');

            const memberSearchInput = document.getElementById('memberSearch');
            const govtFilterInput = document.getElementById('memberGovtFilter');
            const memberRows = Array.from(document.querySelectorAll('.member-pick-col'));
            const memberCards = Array.from(document.querySelectorAll('.member-pick-card'));
            const clickTimers = new Map();
            const draftKey = 'goud_chit_step1_draft';
            const hasOldInput = {{ old('chit_name') || old('chit_type') || old('duration_months') ? 'true' : 'false' }};

            let selectedCounts = {};
            try {
                const parsed = JSON.parse(selectedMembersInput.value || '{}');
                if (parsed && typeof parsed === 'object') {
                    Object.keys(parsed).forEach(function (memberId) {
                        const count = Number(parsed[memberId]);
                        if (Number.isInteger(count) && count > 0) {
                            selectedCounts[memberId] = count;
                        }
                    });
                }
            } catch (error) {
                selectedCounts = {};
            }

            function showStep(step) {
                if (step === 1) {
                    stepOnePane.classList.remove('d-none');
                    stepTwoPane.classList.add('d-none');
                    stepIndicator1.classList.add('active');
                    stepIndicator2.classList.remove('active');
                    return;
                }

                stepOnePane.classList.add('d-none');
                stepTwoPane.classList.remove('d-none');
                stepIndicator1.classList.remove('active');
                stepIndicator2.classList.add('active');
            }

            function totalSlotsSelected() {
                return Object.values(selectedCounts).reduce(function (sum, value) {
                    return sum + Number(value || 0);
                }, 0);
            }

            function syncSummary() {
                const total = totalSlotsSelected();
                slotCountNode.textContent = String(total);
                slotValidationAlert.classList.toggle('d-none', total === memberLimit);
            }

            function syncMemberCard(card) {
                const memberId = String(card.dataset.memberId);
                const count = Number(selectedCounts[memberId] || 0);
                const total = totalSlotsSelected();
                const canIncrease = count < maxRepeatPerMember && total < memberLimit;

                const countNode = card.querySelector('.member-slot-count');
                const hintNode = card.querySelector('.member-action-hint');

                if (countNode) {
                    countNode.textContent = String(count);
                }

                card.classList.toggle('member-pick-active', count > 0);
                card.classList.toggle('member-pick-maxed', count >= maxRepeatPerMember);

                if (hintNode) {
                    if (count >= maxRepeatPerMember) {
                        hintNode.textContent = 'Max repeat reached';
                    } else if (!canIncrease && count === 0) {
                        hintNode.textContent = 'All slots are filled';
                    } else {
                        hintNode.textContent = '1x click +1 | 2x click -1';
                    }
                }
            }

            function syncAllCards() {
                memberCards.forEach(syncMemberCard);
                syncSummary();
                selectedMembersInput.value = JSON.stringify(selectedCounts);
            }

            function calculateMonthlyAmount(total, durationMonths) {
                const safeDuration = Math.max(1, Number(durationMonths) || 1);
                return Math.round(total / safeDuration);
            }

            function updateAmountPreview() {
                const planKey = chitNameInput.value;
                const total = Number(planAmounts[planKey] || 0);
                const monthly = calculateMonthlyAmount(total, durationInput.value);

                totalValueInput.value = total.toLocaleString('en-IN');
                monthlyAmountInput.value = monthly.toLocaleString('en-IN');
            }

            function persistStepOneDraft() {
                const selectedType = document.querySelector('input[name="chit_type"]:checked');
                const payload = {
                    chit_name: chitNameInput.value,
                    chit_type: selectedType ? selectedType.value : 'auction',
                    duration_months: Math.max(1, Number(durationInput.value) || 1),
                };

                localStorage.setItem(draftKey, JSON.stringify(payload));
            }

            function restoreStepOneDraft() {
                if (hasOldInput) {
                    return;
                }

                const raw = localStorage.getItem(draftKey);
                if (!raw) {
                    return;
                }

                try {
                    const parsed = JSON.parse(raw);
                    if (!parsed || typeof parsed !== 'object') {
                        return;
                    }

                    if (parsed.chit_name && planAmounts[parsed.chit_name] !== undefined) {
                        chitNameInput.value = parsed.chit_name;
                    }

                    if (parsed.duration_months && Number(parsed.duration_months) > 0) {
                        durationInput.value = String(Math.round(Number(parsed.duration_months)));
                    }

                    if (parsed.chit_type === 'auction' || parsed.chit_type === 'fixed') {
                        const radio = document.querySelector(`input[name="chit_type"][value="${parsed.chit_type}"]`);
                        if (radio) {
                            radio.checked = true;
                        }
                    }
                } catch (error) {
                    // Ignore malformed local draft payload.
                }
            }

            function applyMemberFilters() {
                const search = (memberSearchInput.value || '').trim().toLowerCase();
                const govtFilter = govtFilterInput.value;

                memberRows.forEach(function (row) {
                    const searchText = row.dataset.search || '';
                    const govtStatus = row.dataset.govt || 'missing';
                    const matchesSearch = search === '' || searchText.includes(search);
                    const matchesGovt = govtFilter === 'all' || govtFilter === govtStatus;

                    row.classList.toggle('d-none', !(matchesSearch && matchesGovt));
                });
            }

            function incrementMember(memberId) {
                const total = totalSlotsSelected();
                const current = Number(selectedCounts[memberId] || 0);

                if (total >= memberLimit || current >= maxRepeatPerMember) {
                    return;
                }

                selectedCounts[memberId] = current + 1;
                syncAllCards();
            }

            function decrementMember(memberId) {
                const current = Number(selectedCounts[memberId] || 0);
                if (current <= 0) {
                    return;
                }

                if (current === 1) {
                    delete selectedCounts[memberId];
                } else {
                    selectedCounts[memberId] = current - 1;
                }

                syncAllCards();
            }

            memberCards.forEach(function (card) {
                const memberId = String(card.dataset.memberId);

                card.addEventListener('click', function () {
                    const existingTimer = clickTimers.get(memberId);
                    if (existingTimer) {
                        clearTimeout(existingTimer);
                    }

                    const timer = window.setTimeout(function () {
                        clickTimers.delete(memberId);
                        incrementMember(memberId);
                    }, 210);

                    clickTimers.set(memberId, timer);
                });

                card.addEventListener('dblclick', function () {
                    const existingTimer = clickTimers.get(memberId);
                    if (existingTimer) {
                        clearTimeout(existingTimer);
                        clickTimers.delete(memberId);
                    }

                    decrementMember(memberId);
                });
            });

            nextButton.addEventListener('click', function () {
                if (!chitNameInput.reportValidity() || !durationInput.reportValidity()) {
                    return;
                }

                persistStepOneDraft();
                showStep(2);
            });

            backButton.addEventListener('click', function () {
                showStep(1);
            });

            saveDetailsButton.addEventListener('click', function () {
                if (!chitNameInput.reportValidity() || !durationInput.reportValidity()) {
                    return;
                }

                persistStepOneDraft();
                stepOneSavedAlert.classList.remove('d-none');
                window.setTimeout(function () {
                    stepOneSavedAlert.classList.add('d-none');
                }, 1800);
            });

            memberSearchInput.addEventListener('input', applyMemberFilters);
            govtFilterInput.addEventListener('change', applyMemberFilters);
            chitNameInput.addEventListener('change', updateAmountPreview);
            durationInput.addEventListener('input', updateAmountPreview);
            durationInput.addEventListener('change', updateAmountPreview);

            form.addEventListener('submit', function (event) {
                const total = totalSlotsSelected();
                if (total !== memberLimit) {
                    event.preventDefault();
                    showStep(2);
                    slotValidationAlert.classList.remove('d-none');
                    return;
                }

                selectedMembersInput.value = JSON.stringify(selectedCounts);
                persistStepOneDraft();
                localStorage.removeItem(draftKey);
            });

            restoreStepOneDraft();
            updateAmountPreview();
            syncAllCards();
            applyMemberFilters();
            if ({{ $selectedMemberSlotsCount }} > 0) {
                showStep(2);
            } else {
                showStep(1);
            }
        });
    </script>
@endpush
