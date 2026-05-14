import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { RecurrenceFrequency } from '@/types/budget';

const FREQUENCIES: { value: RecurrenceFrequency; label: string }[] = [
    { value: 'weekly', label: 'Weekly' },
    { value: 'biweekly', label: 'Every two weeks' },
    { value: 'semimonthly', label: 'Twice a month (e.g. 1st & 15th)' },
    { value: 'monthly', label: 'Monthly' },
    { value: 'quarterly', label: 'Quarterly' },
    { value: 'annual', label: 'Annually' },
    { value: 'custom', label: 'One-off / custom' },
];

const DAYS_OF_WEEK = [
    { value: '0', label: 'Sunday' },
    { value: '1', label: 'Monday' },
    { value: '2', label: 'Tuesday' },
    { value: '3', label: 'Wednesday' },
    { value: '4', label: 'Thursday' },
    { value: '5', label: 'Friday' },
    { value: '6', label: 'Saturday' },
];

type Props = {
    frequency: RecurrenceFrequency;
    interval: number;
    anchorDate: string;
    dayOfMonth: number | null;
    secondaryDayOfMonth: number | null;
    dayOfWeek: number | null;
    onChange: (patch: {
        frequency?: RecurrenceFrequency;
        interval?: number;
        anchor_date?: string;
        day_of_month?: number | null;
        secondary_day_of_month?: number | null;
        day_of_week?: number | null;
    }) => void;
};

export function RecurrencePicker({
    frequency,
    interval,
    anchorDate,
    dayOfMonth,
    secondaryDayOfMonth,
    dayOfWeek,
    onChange,
}: Props) {
    const showWeekly = frequency === 'weekly' || frequency === 'biweekly';
    const showSemimonthly = frequency === 'semimonthly';
    const showMonthly =
        frequency === 'monthly' ||
        frequency === 'quarterly' ||
        frequency === 'annual';

    return (
        <div className="grid gap-4 sm:grid-cols-2">
            <div className="grid gap-2">
                <Label htmlFor="frequency">Frequency</Label>
                <Select
                    name="frequency"
                    value={frequency}
                    onValueChange={(value) =>
                        onChange({ frequency: value as RecurrenceFrequency })
                    }
                >
                    <SelectTrigger id="frequency">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {FREQUENCIES.map((f) => (
                            <SelectItem key={f.value} value={f.value}>
                                {f.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="anchor_date">First occurrence</Label>
                <Input
                    id="anchor_date"
                    name="anchor_date"
                    type="date"
                    value={anchorDate}
                    onChange={(e) => onChange({ anchor_date: e.target.value })}
                    required
                />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="interval">Every</Label>
                <Input
                    id="interval"
                    name="interval"
                    type="number"
                    min={1}
                    value={interval}
                    onChange={(e) =>
                        onChange({ interval: Number(e.target.value) || 1 })
                    }
                />
            </div>

            {showWeekly && (
                <div className="grid gap-2">
                    <Label htmlFor="day_of_week">Day of week</Label>
                    <Select
                        name="day_of_week"
                        value={dayOfWeek !== null ? String(dayOfWeek) : ''}
                        onValueChange={(value) =>
                            onChange({ day_of_week: Number(value) })
                        }
                    >
                        <SelectTrigger id="day_of_week">
                            <SelectValue placeholder="Select day" />
                        </SelectTrigger>
                        <SelectContent>
                            {DAYS_OF_WEEK.map((d) => (
                                <SelectItem key={d.value} value={d.value}>
                                    {d.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
            )}

            {showMonthly && (
                <div className="grid gap-2">
                    <Label htmlFor="day_of_month">Day of month</Label>
                    <Input
                        id="day_of_month"
                        name="day_of_month"
                        type="number"
                        min={1}
                        max={31}
                        value={dayOfMonth ?? ''}
                        onChange={(e) =>
                            onChange({
                                day_of_month: e.target.value
                                    ? Number(e.target.value)
                                    : null,
                            })
                        }
                    />
                </div>
            )}

            {showSemimonthly && (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor="day_of_month">First day</Label>
                        <Input
                            id="day_of_month"
                            name="day_of_month"
                            type="number"
                            min={1}
                            max={31}
                            value={dayOfMonth ?? ''}
                            onChange={(e) =>
                                onChange({
                                    day_of_month: e.target.value
                                        ? Number(e.target.value)
                                        : null,
                                })
                            }
                        />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="secondary_day_of_month">
                            Second day
                        </Label>
                        <Input
                            id="secondary_day_of_month"
                            name="secondary_day_of_month"
                            type="number"
                            min={1}
                            max={31}
                            value={secondaryDayOfMonth ?? ''}
                            onChange={(e) =>
                                onChange({
                                    secondary_day_of_month: e.target.value
                                        ? Number(e.target.value)
                                        : null,
                                })
                            }
                        />
                    </div>
                </>
            )}
        </div>
    );
}
