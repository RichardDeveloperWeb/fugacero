<?php

namespace App;

enum ExpenseType: string
{
    case Expense = 'expense';
    case Subscription = 'subscription';
    case AntExpense = 'ant_expense';
}
