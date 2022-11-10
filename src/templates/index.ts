import type { TemplateWithAccounts } from "@/store/templates"
import { useLocalStorage } from "@vueuse/core"

export const templateToApply = useLocalStorage<TemplateWithAccounts|null>('budget-template-to-apply-v1', null)