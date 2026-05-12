@extends('layouts.saas')

@section('title', 'tickIt - Prompt Writer')
@section('header', 'Prompt Writer')

@section('content')
    <div class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-5">
                <h2 class="text-lg font-semibold text-slate-900">Generate a prompt</h2>
                <p class="mt-1 text-sm text-slate-500">Describe the assistant you want and generate a structured system prompt.</p>
            </div>

            <form id="prompt-generator-form" class="space-y-4">
                @csrf

                <div>
                    <label for="description" class="mb-1 block text-sm font-medium text-slate-700">Description</label>
                    <textarea id="description" name="description" rows="5" required
                        class="tc-textarea"
                        placeholder="Describe what the assistant should do, what it should collect, and how it should behave."></textarea>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label for="assistant_type" class="mb-1 block text-sm font-medium text-slate-700">Assistant type</label>
                        <select id="assistant_type" name="assistant_type" class="tc-input">
                            <option value="support">Support</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="mortgage">Mortgage</option>
                            <option value="leasing">Leasing</option>
                        </select>
                    </div>

                    <div>
                        <label for="tone" class="mb-1 block text-sm font-medium text-slate-700">Tone</label>
                        <select id="tone" name="tone" class="tc-input">
                            <option value="professional">Professional</option>
                            <option value="friendly">Friendly</option>
                            <option value="strict">Strict</option>
                        </select>
                    </div>

                    <div>
                        <label for="strictness" class="mb-1 block text-sm font-medium text-slate-700">Strictness</label>
                        <select id="strictness" name="strictness" class="tc-input">
                            <option value="medium">Medium</option>
                            <option value="low">Low</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                </div>

                <fieldset>
                    <legend class="mb-2 text-sm font-medium text-slate-700">Tools enabled</legend>
                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach(['create_ticket', 'book_meeting', 'handoff_human', 'lookup_contact'] as $tool)
                            <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700">
                                <input type="checkbox" name="tools_enabled[]" value="{{ $tool }}" class="tc-accent-control rounded border-slate-300">
                                <span>{{ str_replace('_', ' ', ucfirst($tool)) }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <div id="prompt-generator-errors" class="hidden rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>

                <button type="submit" class="tc-btn-primary">
                    Generate prompt
                </button>
            </form>
        </section>

        <section class="space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Latest output</h2>
                        <p class="mt-1 text-sm text-slate-500">Generated prompts appear here.</p>
                    </div>
                    @if($workspace)
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                            {{ $workspace->name }}
                        </span>
                    @endif
                </div>

                <pre id="prompt-output" class="min-h-[360px] overflow-x-auto rounded-xl bg-slate-950 p-4 text-sm leading-6 text-slate-100">{{ $versions->first()?->output_markdown ?? "No prompt generated yet." }}</pre>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Recent versions</h2>
                <p class="mt-1 text-sm text-slate-500">The last 10 prompt generations for the current workspace.</p>

                <div class="mt-4 space-y-3">
                    @forelse($versions as $version)
                        <button type="button"
                            class="prompt-version-item tc-accent-card-hover block w-full rounded-xl border border-slate-200 px-4 py-3 text-left transition"
                            data-markdown="{{ e($version->output_markdown) }}">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="font-medium text-slate-900">{{ $version->name ?: ucfirst($version->assistant_type) . ' prompt' }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ ucfirst($version->tone) }} tone • {{ ucfirst($version->strictness) }} strictness</p>
                                </div>
                                <span class="text-xs uppercase tracking-[0.18em] text-slate-400">{{ $version->created_at->format('M j') }}</span>
                            </div>
                        </button>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-sm text-slate-500">
                            No prompt versions yet.
                        </div>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.getElementById('prompt-generator-form');
            const output = document.getElementById('prompt-output');
            const errors = document.getElementById('prompt-generator-errors');

            form?.addEventListener('submit', async (event) => {
                event.preventDefault();

                errors.classList.add('hidden');
                errors.textContent = '';

                const formData = new FormData(form);

                try {
                    const response = await fetch('{{ route('app.prompt-writer.generate') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': formData.get('_token'),
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        errors.textContent = data.error || Object.values(data.errors || {}).flat().join(' ');
                        errors.classList.remove('hidden');
                        return;
                    }

                    output.textContent = data.markdown || 'No output returned.';
                    window.location.reload();
                } catch (error) {
                    errors.textContent = 'The prompt could not be generated right now. Please try again.';
                    errors.classList.remove('hidden');
                }
            });

            document.querySelectorAll('.prompt-version-item').forEach((button) => {
                button.addEventListener('click', () => {
                    output.textContent = button.dataset.markdown || '';
                });
            });
        })();
    </script>
@endpush
