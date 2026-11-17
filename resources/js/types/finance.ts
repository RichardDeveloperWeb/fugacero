export type Finance = {
    currency: string;
};

export type Category = {
    id: number;
    user_id: number | null;
    name: string;
    color: string;
    icon: string | null;
    is_default: boolean;
    expenses_count: number;
};

export type Budget = {
    id: number;
    user_id: number;
    year: number;
    month: number;
    amount: string;
};

export type BudgetCategoryBudget = {
    id: number;
    name: string;
    color: string;
    icon: string | null;
    is_default: boolean;
    limit: number;
    spent: number;
    remaining: number;
    progress: number;
};

export type BudgetSummary = {
    amount: number;
    spent: number;
    available: number;
    allocated: number;
    usage_percent: number;
};

export type ExpenseTypeValue = 'expense' | 'subscription' | 'ant_expense';

export type Expense = {
    id: number;
    user_id: number;
    category_id: number;
    description: string;
    amount: string;
    type: ExpenseTypeValue;
    spent_at: string;
    category: Pick<Category, 'id' | 'name' | 'color' | 'icon'>;
};

export type ExpenseFilters = {
    month: string | null;
    type: ExpenseTypeValue | null;
    category_id: number | null;
    search: string | null;
};
