document.addEventListener('alpine:init', () => {
    Alpine.data('searchableSelect', (field) => ({
        open: false,
        search: '',
        label: '',

        init() {
            this.refreshLabel();
            this.$wire.$watch(field, () => this.refreshLabel());
        },

        currentValue() {
            return String(this.$wire.get(field) ?? '');
        },

        refreshLabel() {
            const current = this.currentValue();
            const match = [...this.$root.querySelectorAll('[data-value]')].find((el) => el.dataset.value === current);
            this.label = match ? match.dataset.label : '';
        },

        toggle() {
            this.open = ! this.open;
            if (this.open) {
                this.$nextTick(() => this.$refs.search.focus());
            }
        },

        close() {
            this.open = false;
            this.search = '';
        },

        choose(value) {
            this.$wire.set(field, value);
            this.close();
        },

        matches(el) {
            const term = this.search.toLowerCase();
            if (term === '') {
                return true;
            }
            return (el.dataset.label || '').toLowerCase().includes(term);
        },

        get noResults() {
            const term = this.search.toLowerCase();
            if (term === '') {
                return false;
            }
            return ! [...this.$root.querySelectorAll('[data-value]')]
                .some((el) => (el.dataset.label || '').toLowerCase().includes(term));
        },

        isSelected(value) {
            return this.currentValue() === String(value);
        },
    }));
});
