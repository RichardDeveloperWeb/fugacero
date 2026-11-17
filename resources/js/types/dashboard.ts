import type { BudgetSummary } from './finance';

export type DashboardTopCategory = {
    id: number;
    name: string;
    color: string;
    icon: string | null;
    total: number;
};

export type DashboardStats = {
    month: string;
    summary: BudgetSummary;
    top_category: DashboardTopCategory | null;
};

export type AdminTopCategory = DashboardTopCategory & {
    expenses_count: number;
};

export type AdminDashboardStats = {
    users: number;
    users_with_expenses: number;
    total_spent: number;
    average_per_user: number;
    top_categories: AdminTopCategory[];
};
