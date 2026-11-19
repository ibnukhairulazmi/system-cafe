export type MenuItem = {
    id?: number;
    product_id?: number;
    name: string;
    desc: string;
    price: number;
    image: string;
    available?: boolean;
};

export type Category = {
    id: string;
    label: string;
    title: string;
    subtitle: string;
    items: MenuItem[];
};