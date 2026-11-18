import type { BudgetSummary } from './finance';

export type DashboardTopCategory = {
    id: number;
    name: string;
    color: string;
    icon: string | null;
    total: number;
};

export type ChartCategory = DashboardTopCategory & {
    expenses_count: number;
};

export type MonthlySeries = {
    labels: string[];
    spent: number[];
    budget: number[];
};

export type CategoryBreakdown = {
    categories: ChartCategory[];
    other_total: number;
};

export type MonthlyPace = {
    labels: string[];
    cumulative: number[];
    ideal: number[];
    budget: number;
    days_elapsed: number;
};

export type AdminMonthlySeries = {
    labels: string[];
    total_spent: number[];
};

export type DashboardStats = {
    month: string;
    summary: BudgetSummary;
    top_category: DashboardTopCategory | null;
};

export type AdminTopCategory = ChartCategory;

export type DashboardCharts = {
    monthly_spending: MonthlySeries;
    category_breakdown: CategoryBreakdown;
    monthly_pace: MonthlyPace;
};

export type AdminDashboardCharts = {
    monthly_spending: AdminMonthlySeries;
};

export type AdminDashboardStats = {
    users: number;
    users_with_expenses: number;
    total_spent: number;
    average_per_user: number;
    top_categories: AdminTopCategory[];
};
