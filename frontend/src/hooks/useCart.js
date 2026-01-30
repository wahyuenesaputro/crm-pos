import { create } from 'zustand';
import { persist } from 'zustand/middleware';

const useCart = create(
    persist(
        (set, get) => ({
            cart: [],
            addToCart: (product, variant = null, quantity = 1) => {
                const selectedVariant = variant || product.variants?.[0];
                if (!selectedVariant) return; // Should not happen

                set((state) => {
                    const existingItem = state.cart.find(
                        (item) => item.variant_id === selectedVariant.id
                    );

                    if (existingItem) {
                        return {
                            cart: state.cart.map((item) =>
                                item.variant_id === selectedVariant.id
                                    ? { ...item, quantity: item.quantity + quantity }
                                    : item
                            ),
                        };
                    }

                    return {
                        cart: [
                            ...state.cart,
                            {
                                id: Date.now(), // Temporary ID for UI key
                                variant_id: selectedVariant.id,
                                product_id: product.id,
                                name: product.name + (product.variants?.length > 1 ? ` (${selectedVariant.name})` : ''),
                                price: parseFloat(selectedVariant.selling_price),
                                quantity: quantity,
                            },
                        ],
                    };
                });
            },
            updateQuantity: (id, delta) => {
                set((state) => {
                    const newCart = state.cart.map((item) => {
                        if (item.id === id) {
                            return { ...item, quantity: Math.max(1, item.quantity + delta) };
                        }
                        return item;
                    });
                    return { cart: newCart };
                });
            },
            removeFromCart: (id) => {
                set((state) => ({
                    cart: state.cart.filter((item) => item.id !== id),
                }));
            },
            clearCart: () => set({ cart: [] }),
        }),
        {
            name: 'pos-cart-storage', // unique name
        }
    )
);

export default useCart;
