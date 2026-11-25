import type { LucideIcon } from "lucide-react";
import { Inbox } from "lucide-react";

export function EmptyRow({
  colSpan,
  icon: Icon = Inbox,
  message,
}: {
  colSpan: number;
  icon?: LucideIcon;
  message: string;
}) {
  return (
    <tr>
      <td colSpan={colSpan} className="px-5 py-16 text-center">
        <Icon className="mx-auto mb-3 h-8 w-8 text-slate-300" strokeWidth={1.5} />
        <p className="text-sm text-slate-400">{message}</p>
      </td>
    </tr>
  );
}
