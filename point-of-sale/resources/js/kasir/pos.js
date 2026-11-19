/**
 * Logic Alpine.js untuk Point of Sale (POS) Kasir
 * Ruang Teduh Cafe System
 */

export function posComponent(cfg) {
    return {
        products: cfg.products || [],
        categories: cfg.categories || [],
        taxEnabled: cfg.taxEnabled || false,
        taxRate: cfg.taxRate || 0,
        search: '',
        category: 'all',
        cart: [],
        discountPercent: cfg.discount || 0,
        useDiscount: true,
        rounding: cfg.rounding || 500,
        method: 'cash',
        paid: '',
        orderType: 'dine_in',
        tableId: null,
        tables: cfg.tables || [],
        busy: false,
        error: '',
        done: null,
        page: 1,
        perPage: 12,

        init() {
            this.$watch('search', () => { this.page = 1; });
        },

        get filtered() {
            const q = this.search.trim().toLowerCase();
            return this.products.filter(p =>
                (this.category === 'all' || p.category_id === this.category) &&
                (q === '' || p.name.toLowerCase().includes(q))
            );
        },

        get totalPages() {
            return Math.max(1, Math.ceil(this.filtered.length / this.perPage));
        },

        get paginatedProducts() {
            const start = (this.page - 1) * this.perPage;
            return this.filtered.slice(start, start + this.perPage);
        },

        get pageStart() {
            return this.filtered.length === 0 ? 0 : (this.page - 1) * this.perPage + 1;
        },

        get pageEnd() {
            return Math.min(this.page * this.perPage, this.filtered.length);
        },

        setCategory(c) {
            this.category = c;
            this.page = 1;
        },

        qtyInCart(id) {
            const l = this.cart.find(x => x.id === id);
            return l ? l.qty : 0;
        },

        add(p) {
            if (p.max !== null && this.qtyInCart(p.id) >= p.max) return;
            const l = this.cart.find(x => x.id === p.id);
            if (l) {
                l.qty++;
            } else {
                this.cart.push({
                    id: p.id,
                    name: p.name,
                    price: p.price,
                    qty: 1,
                    note: '',
                    max: p.max
                });
            }
        },

        inc(l) {
            if (l.max === null || l.qty < l.max) l.qty++;
        },

        dec(l) {
            if (l.qty > 1) {
                l.qty--;
            } else {
                this.remove(l);
            }
        },

        remove(l) {
            this.cart = this.cart.filter(x => x !== l);
        },

        get subtotal() {
            return this.cart.reduce((s, l) => s + l.price * l.qty, 0);
        },

        get discount() {
            if (!this.useDiscount) return 0;
            return Math.min(Math.round(this.subtotal * this.discountPercent / 100), this.subtotal);
        },

        get taxBase() {
            return this.subtotal - this.discount;
        },

        get tax() {
            return this.taxEnabled ? Math.round(this.taxBase * this.taxRate / 100) : 0;
        },

        get rawTotal() {
            return this.taxBase + this.tax;
        },

        get total() {
            const step = Number(this.rounding) || 0;
            if (step <= 0) {
                return this.rawTotal;
            }
            return Math.round(this.rawTotal / step) * step;
        },

        get roundingAmount() {
            return this.total - this.rawTotal;
        },

        get paidNumber() {
            return parseFloat(this.paid) || 0;
        },

        get change() {
            return this.method === 'cash' ? Math.max(0, this.paidNumber - this.total) : 0;
        },

        get canPay() {
            return !this.busy && this.cart.length > 0 &&
                (this.orderType !== 'dine_in' || this.tableId !== null) &&
                (this.method !== 'cash' || this.paidNumber >= this.total);
        },

        get selectedTable() {
            return this.tables.find(t => t.id === this.tableId) || null;
        },

        get tablesByArea() {
            const groups = {};
            this.tables.forEach(t => {
                const key = t.area || 'Lainnya';
                (groups[key] = groups[key] || []).push(t);
            });
            return Object.entries(groups).map(([area, items]) => ({ area, items }));
        },

        setOrderType(type) {
            this.orderType = type;
            if (type === 'takeaway') this.tableId = null;
        },

        selectTable(t) {
            if (t.occupied) return;
            this.tableId = this.tableId === t.id ? null : t.id;
        },

        quick(amount) {
            this.paid = amount;
        },

        roundedUp(step) {
            return Math.ceil(this.total / step) * step;
        },

        fmt(n) {
            return 'Rp ' + Math.round(n).toLocaleString('id-ID');
        },

        printReceipt(url) {
            if (!url) return;

            let frame = document.getElementById('print-frame');
            if (!frame) {
                frame = document.createElement('iframe');
                frame.id = 'print-frame';
                frame.setAttribute('aria-hidden', 'true');
                frame.style.cssText =
                    'position:fixed;right:0;bottom:0;width:0;height:0;border:0;visibility:hidden;';
                document.body.appendChild(frame);
            }

            const sep = url.includes('?') ? '&' : '?';
            frame.src = url + sep + 'autoprint=1&_t=' + Date.now();
        },

        async submit() {
            if (!this.canPay) return;
            this.busy = true;
            this.error = '';
            try {
                const res = await fetch(cfg.checkoutUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': cfg.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        items: this.cart.map(l => ({
                            product_id: l.id,
                            quantity: l.qty,
                            note: l.note || null
                        })),
                        apply_discount: this.discount > 0,
                        order_type: this.orderType,
                        cafe_table_id: this.orderType === 'dine_in' ? this.tableId : null,
                        payment_method: this.method,
                        paid_amount: this.method === 'cash' ? this.paidNumber : this.total,
                    }),
                });

                const data = await res.json().catch(() => ({}));
                if (res.ok) {
                    this.done = { ...data, table_name: this.selectedTable ? this.selectedTable.name : null };
                    this.printReceipt(data.receipt_url);
                } else if (res.status === 419) {
                    this.error = 'Sesi habis. Muat ulang halaman lalu coba lagi.';
                } else if (res.status === 409 && data.message) {
                    this.error = data.message;
                } else if (data.errors) {
                    this.error = Object.values(data.errors).flat().join(' ');
                } else {
                    this.error = data.message || 'Terjadi kesalahan. Coba lagi.';
                }
            } catch (e) {
                this.error = 'Tidak dapat terhubung ke server. Periksa koneksi lalu coba lagi.';
            } finally {
                this.busy = false;
            }
        },
    };
}

if (typeof window !== 'undefined') {
    window.posComponent = posComponent;
    window.pos = posComponent;
}
