/**
 * Logic Alpine.js untuk Form Purchase Order / Pemesanan Bahan (Admin)
 * Ruang Teduh Cafe System
 */

export function purchaseOrderFormComponent(ingredientMap = {}, initialRows = []) {
    return {
        ingredients: ingredientMap || {},
        rows: initialRows.length > 0 ? initialRows : [{ ingredient_id: '', quantity: '', unit_price: '' }],

        add() {
            this.rows.push({ ingredient_id: '', quantity: '', unit_price: '' });
        },

        remove(index) {
            this.rows.splice(index, 1);
        },

        unit(id) {
            return this.ingredients[id] ? this.ingredients[id].unit : '';
        },

        prefill(row) {
            if (!row.unit_price && this.ingredients[row.ingredient_id]) {
                row.unit_price = this.ingredients[row.ingredient_id].cost;
            }
        },

        sub(row) {
            return (parseFloat(row.quantity) || 0) * (parseFloat(row.unit_price) || 0);
        },

        get total() {
            return this.rows.reduce((sum, r) => sum + this.sub(r), 0);
        },

        fmt(n) {
            return 'Rp ' + Math.round(n).toLocaleString('id-ID');
        },
    };
}

if (typeof window !== 'undefined') {
    window.purchaseOrderFormComponent = purchaseOrderFormComponent;
}
