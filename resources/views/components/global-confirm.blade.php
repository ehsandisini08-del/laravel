<div
    x-data="{
        show: false,
        message: '',
        callback: null,
        confirmLabel: 'Hapus',
        confirmColor: 'red',
        init() {
            window.addEventListener('open-confirm', (e) => {
                console.log('open-confirm event received:', e.detail);
                this.message = e.detail.message;
                this.callback = e.detail.callback;
                this.confirmLabel = e.detail.confirmLabel || 'Hapus';
                this.confirmColor = e.detail.confirmColor || 'red';
                console.log('Dialog state:', { confirmLabel: this.confirmLabel, confirmColor: this.confirmColor });
                this.show = true;
            });
        },
        confirm() {
            if (this.callback) this.callback();
            this.show = false;
        },
        cancel() {
            this.show = false;
            window.dispatchEvent(new CustomEvent('confirm-cancelled'));
        }
    }"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    style="display: none;"
>
    <div class="fixed inset-0 bg-gray-500 opacity-75" x-on:click="cancel()"></div>

    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative w-full max-w-md rounded-lg bg-white dark:bg-gray-800 shadow-xl">
            <div class="p-6">
                <div class="flex items-center gap-4">
                    <div class="h-12 w-12 shrink-0 rounded-full flex items-center justify-center"
                         :class="{
                             'bg-red-100 dark:bg-red-900/20': confirmColor === 'red',
                             'bg-green-100 dark:bg-green-900/20': confirmColor === 'green',
                             'bg-blue-100 dark:bg-blue-900/20': confirmColor === 'blue'
                         }">
                        <svg class="h-6 w-6" :class="{
                                 'text-red-600': confirmColor === 'red',
                                 'text-green-600': confirmColor === 'green',
                                 'text-blue-600': confirmColor === 'blue'
                             }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Konfirmasi</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" x-text="message"></p>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" x-on:click="cancel()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Batal</button>
                    <button type="button" x-on:click="confirm()" class="px-4 py-2 text-white rounded-lg transition-colors"
                            :class="{
                                'bg-red-600 hover:bg-red-700': confirmColor === 'red',
                                'bg-green-600 hover:bg-green-700': confirmColor === 'green',
                                'bg-blue-600 hover:bg-blue-700': confirmColor === 'blue'
                            }"
                            x-text="confirmLabel"></button>
                </div>
            </div>
        </div>
    </div>
</div>