/**
 * Logic Alpine.js untuk Form Resep Menu (Admin)
 * Ruang Teduh Cafe System
 */

export function recipeFormComponent(ingredientMap = {}, initialRows = []) {
    return {
        ingredients: ingredientMap || {},
        rows: initialRows.length > 0 ? initialRows : [{ ingredient_id: '', quantity: '' }],

        add() {
            this.rows.push({ ingredient_id: '', quantity: '' });
        },

        remove(index) {
            this.rows.splice(index, 1);
        },

        unit(id) {
            return this.ingredients[id] ? this.ingredients[id].unit : '';
        },
    };
}

if (typeof window !== 'undefined') {
    window.recipeFormComponent = recipeFormComponent;
}
