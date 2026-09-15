<x-dashboard-layout
    title="File Guest Report"
    subtitle="Record a lost or found item on behalf of a walk-in guest"
    active="guest-reports"
>
    @if (session('status'))
        <div class="auth-alert auth-alert-success" style="margin-bottom:20px;">
            <i class="ti ti-circle-check"></i> {{ session('status') }}
        </div>
    @endif

    <div class="glass-card" style="max-width:640px;">
        <form method="POST" action="{{ route('officer.guest-reports.store') }}" enctype="multipart/form-data">
            @csrf

            <h4 style="font-size:14px;font-weight:700;color:var(--primary);margin-bottom:16px;display:flex;align-items:center;gap:8px;border-bottom:1px solid var(--glass-border);padding-bottom:8px;">
                <i class="ti ti-user-plus"></i> Guest Details
            </h4>

            <div class="form-group">
                <label for="guest_name">Guest's name</label>
                <input type="text" id="guest_name" name="guest_name" value="{{ old('guest_name') }}"
                       placeholder="Full name" required>
                @error('guest_name') <span class="field-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="guest_phone">Phone <span class="hint">(optional)</span></label>
                    <input type="text" id="guest_phone" name="guest_phone" value="{{ old('guest_phone') }}">
                    @error('guest_phone') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="guest_email">Email <span class="hint">(optional)</span></label>
                    <input type="email" id="guest_email" name="guest_email" value="{{ old('guest_email') }}">
                    @error('guest_email') <span class="field-error">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="guest_id_type">ID type <span class="hint">(optional)</span></label>
                    <input type="text" id="guest_id_type" name="guest_id_type" value="{{ old('guest_id_type') }}"
                           placeholder="e.g. National ID, Passport">
                    @error('guest_id_type') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="guest_id_number">ID number <span class="hint">(optional)</span></label>
                    <input type="text" id="guest_id_number" name="guest_id_number" value="{{ old('guest_id_number') }}">
                    @error('guest_id_number') <span class="field-error">{{ $message }}</span> @enderror
                </div>
            </div>

            <h4 style="font-size:14px;font-weight:700;color:var(--primary);margin:24px 0 16px;display:flex;align-items:center;gap:8px;border-bottom:1px solid var(--glass-border);padding-bottom:8px;">
                <i class="ti ti-box"></i> Item Details
            </h4>

            <input type="hidden" name="type" id="type" value="{{ old('type', 'lost') }}">
            <div class="type-toggle">
                <button type="button" class="type-btn {{ old('type', 'lost') === 'lost' ? 'active' : '' }}"
                        onclick="setReportType('lost', this)">
                    <i class="ti ti-circle-x"></i> Lost
                </button>
                <button type="button" class="type-btn {{ old('type') === 'found' ? 'active' : '' }}"
                        onclick="setReportType('found', this)">
                    <i class="ti ti-circle-check"></i> Found
                </button>
            </div>

            <div class="form-group">
                <label for="name">Item name</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}"
                       placeholder="e.g. Black Samsung phone" required>
                @error('name') <span class="field-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category" required>
                        <option value="">— Select —</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat }}" @selected(old('category') === $cat)>{{ $cat }}</option>
                        @endforeach
                    </select>
                    @error('category') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" value="{{ old('location') }}"
                           list="location-options" placeholder="e.g. Main Library" required>
                    <datalist id="location-options">
                        @foreach ($locations as $loc)
                            <option value="{{ $loc }}"></option>
                        @endforeach
                    </datalist>
                    @error('location') <span class="field-error">{{ $message }}</span> @enderror
                </div>
            </div>

            <x-map-picker target="location" :known="$mapLocations" />

            <div class="form-group">
                <label for="date">Date lost / found</label>
                <input type="date" id="date" name="date" value="{{ old('date', date('Y-m-d')) }}"
                       max="{{ date('Y-m-d') }}" required>
                @error('date') <span class="field-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3"
                          placeholder="Colour, brand, distinguishing marks — anything that helps identify it.">{{ old('description') }}</textarea>
                @error('description') <span class="field-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label for="contact">Contact info <span class="hint">(optional)</span></label>
                <input type="text" id="contact" name="contact" value="{{ old('contact') }}"
                       placeholder="Phone or email the office can reach the guest on, if different from above">
                @error('contact') <span class="field-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label>Photo <span class="hint">(optional)</span></label>
                <label for="image" class="image-upload-area" style="display:block;">
                    <i class="ti ti-camera-plus"></i>
                    <p id="file-name">Tap to add a photo, if available</p>
                </label>
                <input type="file" id="image" name="image" accept="image/*" style="display:none;" onchange="showFileName(this)">
                @error('image') <span class="field-error">{{ $message }}</span> @enderror
            </div>

            <button type="submit" class="submit-btn">
                <i class="ti ti-send"></i> File Report
            </button>
        </form>
    </div>

    <script>
        function setReportType(type, btn) {
            document.getElementById('type').value = type;
            document.querySelectorAll('.type-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        }

        function showFileName(input) {
            const label = document.getElementById('file-name');
            if (label) label.textContent = input.files.length ? input.files[0].name : 'Tap to add a photo, if available';
        }
    </script>
</x-dashboard-layout>
