<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Delete Account') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
        </p>
    </header>

    <div x-data="{ show: @json($errors->userDeletion->isNotEmpty()) }">
        <button
            type="button"
            @click.prevent="show = true"
            class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
        >
            {{ __('Delete Account') }}
        </button>

        <div
            x-show="show"
            x-cloak
            class="fixed inset-0 z-50 flex items-start justify-center p-4 sm:p-6"
            aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
        >
            <div class="fixed inset-0 bg-black/50" @click="show = false"></div>

            <div class="relative bg-white rounded-lg overflow-hidden shadow-xl max-w-2xl w-full m-4 z-10">
                <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
                    @csrf
                    @method('delete')

                    <h2 class="text-lg font-medium text-gray-900">
                        {{ __('Are you sure you want to delete your account?') }}
                    </h2>

                    <p class="mt-1 text-sm text-gray-600">
                        {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                    </p>

                    <div class="mt-6">
                        <label for="password" class="sr-only">{{ __('Password') }}</label>

                        <input
                            id="password"
                            name="password"
                            type="password"
                            class="mt-1 block w-3/4 rounded-md border-gray-300 shadow-sm focus:ring-orange-500 focus:border-orange-500"
                            placeholder="{{ __('Password') }}"
                        />

                        @if ($errors->userDeletion->get('password'))
                            <p class="mt-2 text-sm text-red-600">{{ $errors->userDeletion->first('password') }}</p>
                        @endif
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="button" @click="show = false" class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                            {{ __('Cancel') }}
                        </button>

                        <button type="submit" class="ms-3 inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            {{ __('Delete Account') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
