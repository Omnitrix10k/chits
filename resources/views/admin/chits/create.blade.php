@extends('layouts.app-custom')

@section('title', __('Create Chit'))

@section('header', __('Create Chit'))

@php
    $chitsCssVersion = file_exists(public_path('css/chits.css'))
        ? filemtime(public_path('css/chits.css'))
        : time();

    $selectedPlanText = trim((string) old('chit_name', ''));

    $selectedType = old('chit_type', 'auction');
    $durationPreview = max((int) old('duration_months', 20), 1);
    $memberLimitPreview = max((int) old('member_limit', $memberLimit), 1);
    $totalAmountPreview = max((int) old('total_amount', 100000), 1);
    $monthlyAmountPreview = \App\Models\Chit::calculateMonthlyAmount((int) $totalAmountPreview, $durationPreview, $memberLimitPreview);
    $selectedMemberSlotsCount = array_sum($selectedMembers);
@endphp

@push('styles')
    <link href="{{ asset('css/chits.css') }}?v={{ $chitsCssVersion }}" rel="stylesheet">
@endpush

@section('content')
    <section class="section chit-create-page">
        <div class="card chit-create-hero-card mb-3">
            <div class="card-body p-3 p-lg-4">
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                    <div>
                        <p class="chit-overview-kicker mb-1">Chit Workflow</p>
                        <h4 class="chit-overview-title mb-1">Create New Chit</h4>
                        <p class="chit-overview-subtitle mb-0">
                            Configure chit details, define member capacity, and assign slots with validation in a single guided flow.
                        </p>
                    </div>
                    <a href="{{ route('admin.chits.index') }}" class="btn btn-outline-secondary chit-overview-cta">
                        <i class="bi bi-arrow-left me-1"></i>Back To Chits
                    </a>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12">
                <div class="card chit-rules-card">
                    <div class="card-body py-3">
                        <h6 class="mb-2">Chit Creation Rules</h6>
                        <ul class="mb-0">
                            <li>Select one chit plan and type first.</li>
                            <li>Set total members in step 1; the same slot count must be assigned in step 2.</li>
                            <li>Maximum repeats per member is dynamic up to <strong>{{ $maxRepeatPerMember }}</strong> per chit.</li>
                            <li>Enter total chit value manually; monthly amount is auto-calculated as total value / total members.</li>
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
                You must assign exactly <strong id="slotValidationLimit">{{ $memberLimitPreview }}</strong> member slots before creating the chit.
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
                            <input
                                id="chitName"
                                type="text"
                                name="chit_name"
                                class="form-control"
                                list="chitNameSuggestions"
                                value="{{ $selectedPlanText }}"
                                placeholder="Enter chit name"
                                required
                            >
                            <datalist id="chitNameSuggestions">
                                @foreach ($planOptions as $planKey => $plan)
                                    <option value="{{ $plan['label'] }}"></option>
                                @endforeach
                            </datalist>
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

                        <div class="col-lg-3">
                            <label for="totalValue" class="form-label">Total Chit Value</label>
                            <input
                                id="totalValue"
                                type="number"
                                name="total_amount"
                                class="form-control"
                                min="1"
                                step="1"
                                value="{{ $totalAmountPreview }}"
                                required
                            >
                        </div>

                        <div class="col-lg-3">
                            <label for="memberLimit" class="form-label">Total Members</label>
                            <input
                                id="memberLimit"
                                type="number"
                                name="member_limit"
                                class="form-control"
                                min="1"
                                max="250"
                                value="{{ $memberLimitPreview }}"
                                required
                            >
                        </div>

                        <div class="col-lg-3">
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

                        <div class="col-lg-3">
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
                            Slots Filled: <span id="slotCount">{{ $selectedMemberSlotsCount }}</span>/<span id="slotLimit">{{ $memberLimitPreview }}</span>
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
            const initialMemberLimit = {{ $memberLimitPreview }};
            const maxRepeatPerMemberCap = {{ $maxRepeatPerMember }};

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
            const memberLimitInput = document.getElementById('memberLimit');
            const monthlyAmountInput = document.getElementById('monthlyAmount');

            const selectedMembersInput = document.getElementById('selectedMembersInput');
            const slotCountNode = document.getElementById('slotCount');
            const slotLimitNode = document.getElementById('slotLimit');
            const slotValidationLimitNode = document.getElementById('slotValidationLimit');
            const slotValidationAlert = document.getElementById('slotValidationAlert');

            const memberSearchInput = document.getElementById('memberSearch');
            const govtFilterInput = document.getElementById('memberGovtFilter');
            const memberRows = Array.from(document.querySelectorAll('.member-pick-col'));
            const memberCards = Array.from(document.querySelectorAll('.member-pick-card'));
            const clickTimers = new Map();
            const draftKey = 'goud_chit_step1_draft';
            const hasOldInput = {{ old('chit_name') || old('chit_type') || old('duration_months') || old('total_amount') || old('member_limit') ? 'true' : 'false' }};

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

            function currentMemberLimit() {
                const parsed = Math.round(Number(memberLimitInput.value) || initialMemberLimit || 1);
                return Math.max(1, parsed);
            }

            function currentMaxRepeatPerMember() {
                return Math.max(1, Math.min(maxRepeatPerMemberCap, currentMemberLimit()));
            }

            function syncSummary() {
                const total = totalSlotsSelected();
                const memberLimit = currentMemberLimit();

                slotCountNode.textContent = String(total);
                slotLimitNode.textContent = String(memberLimit);
                slotValidationLimitNode.textContent = String(memberLimit);
                slotValidationAlert.classList.toggle('d-none', total === memberLimit);
            }

            function syncMemberCard(card) {
                const memberId = String(card.dataset.memberId);
                const count = Number(selectedCounts[memberId] || 0);
                const total = totalSlotsSelected();
                const memberLimit = currentMemberLimit();
                const maxRepeatPerMember = currentMaxRepeatPerMember();
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
                const safeMemberLimit = currentMemberLimit();

                // Duration is validated and stored for schedule handling; per-member amount is total/member count.
                if (safeDuration < 1) {
                    return 0;
                }
                return Math.round(total / safeMemberLimit);
            }

            function updateAmountPreview() {
                const total = Math.max(0, Math.round(Number(totalValueInput.value || 0)));
                const monthly = calculateMonthlyAmount(total, durationInput.value);

                monthlyAmountInput.value = monthly.toLocaleString('en-IN');
            }

            function persistStepOneDraft() {
                const selectedType = document.querySelector('input[name="chit_type"]:checked');
                const payload = {
                    chit_name: chitNameInput.value.trim(),
                    chit_type: selectedType ? selectedType.value : 'auction',
                    total_amount: Math.max(1, Math.round(Number(totalValueInput.value) || 1)),
                    member_limit: currentMemberLimit(),
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

                    if (parsed.chit_name && parsed.chit_name.trim() !== '') {
                        chitNameInput.value = parsed.chit_name;
                    }

                    if (parsed.duration_months && Number(parsed.duration_months) > 0) {
                        durationInput.value = String(Math.round(Number(parsed.duration_months)));
                    }

                    if (parsed.total_amount && Number(parsed.total_amount) > 0) {
                        totalValueInput.value = String(Math.round(Number(parsed.total_amount)));
                    }

                    if (parsed.member_limit && Number(parsed.member_limit) > 0) {
                        memberLimitInput.value = String(Math.round(Number(parsed.member_limit)));
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
                const memberLimit = currentMemberLimit();
                const maxRepeatPerMember = currentMaxRepeatPerMember();

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

                card.addEventListener('click', function (event) {
                    const existingTimer = clickTimers.get(memberId);
                    if (existingTimer) {
                        clearTimeout(existingTimer);
                        clickTimers.delete(memberId);
                    }

                    // 1 click => +1, 2 clicks => -1 (must not apply +1 first).
                    if (event.detail >= 2) {
                        decrementMember(memberId);
                        return;
                    }

                    const timer = window.setTimeout(function () {
                        clickTimers.delete(memberId);
                        incrementMember(memberId);
                    }, 320);

                    clickTimers.set(memberId, timer);
                });
            });

            nextButton.addEventListener('click', function () {
                if (!chitNameInput.reportValidity() || !memberLimitInput.reportValidity() || !durationInput.reportValidity()) {
                    return;
                }

                persistStepOneDraft();
                showStep(2);
            });

            backButton.addEventListener('click', function () {
                showStep(1);
            });

            saveDetailsButton.addEventListener('click', function () {
                if (!chitNameInput.reportValidity() || !memberLimitInput.reportValidity() || !durationInput.reportValidity()) {
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
            totalValueInput.addEventListener('input', updateAmountPreview);
            totalValueInput.addEventListener('change', updateAmountPreview);
            totalValueInput.addEventListener('blur', function () {
                const rounded = Math.max(1, Math.round(Number(totalValueInput.value) || 1));
                totalValueInput.value = String(rounded);
                updateAmountPreview();
            });
            durationInput.addEventListener('input', updateAmountPreview);
            durationInput.addEventListener('change', updateAmountPreview);
            memberLimitInput.addEventListener('input', function () {
                const normalized = currentMemberLimit();
                memberLimitInput.value = String(normalized);
                updateAmountPreview();
                syncAllCards();
            });
            memberLimitInput.addEventListener('change', function () {
                const normalized = currentMemberLimit();
                memberLimitInput.value = String(normalized);
                updateAmountPreview();
                syncAllCards();
            });

            form.addEventListener('submit', function (event) {
                const total = totalSlotsSelected();
                const memberLimit = currentMemberLimit();
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
            memberLimitInput.value = String(currentMemberLimit());
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
