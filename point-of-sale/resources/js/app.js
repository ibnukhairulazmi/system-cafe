import Alpine from 'alpinejs';
import { posComponent } from './kasir/pos';
import { recipeFormComponent } from './admin/recipe';
import { purchaseOrderFormComponent } from './admin/purchase-order';

window.Alpine = Alpine;

Alpine.data('pos', posComponent);
Alpine.data('recipeForm', recipeFormComponent);
Alpine.data('purchaseOrderForm', purchaseOrderFormComponent);

Alpine.start();
