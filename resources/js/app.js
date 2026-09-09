document.addEventListener('alpine:init', () => {
    Alpine.data('searchableSelect', (field) => ({
        open: false,
        search: '',
        value: null,

        init() {
            this.value = this.$wire.entangle(field);
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
            this.value = value;
            this.close();
        },

        matches(el) {
            if (this.search === '') {
                return true;
            }
            return (el.dataset.label || '').toLowerCase().includes(this.search.toLowerCase());
        },

        hasResults() {
            return [...this.$el.querySelectorAll('[data-value]')].some((el) => this.matches(el));
        },

        isSelected(value) {
            return String(this.value ?? '') === String(value);
        },

        selectedLabel() {
            const current = String(this.value ?? '');
            const match = [...this.$el.querySelectorAll('[data-value]')].find((el) => el.dataset.value === current);
            return match ? match.dataset.label : '';
        },
    }));
});
