/**
 * Composable for formatting currency input values
 * Handles decimal formatting and ensures proper currency format
 */
export function useCurrencyFormatter() {
  /**
   * Formats a currency input value to ensure proper decimal places
   * @param value - The input value to format
   * @returns Formatted currency string with exactly 2 decimal places
   */
  function formatCurrencyInput(value: string): string {
    const split = value?.split(".");
    const afterDecimal = split[1];

    if (!afterDecimal || afterDecimal.length !== 2) {
      const beforeDecimal = split[0] ?? "0";
      let replacementDecimal = afterDecimal ?? "00";

      if (afterDecimal?.length) {
        if (afterDecimal.length === 1) {
          replacementDecimal = `${afterDecimal}0`;
        } else {
          replacementDecimal = String(
            Math.round(
              Number(
                afterDecimal.slice(0, 2) + "." + afterDecimal.slice(2)
              )
            )
          );
        }
      }

      return `${beforeDecimal}.${replacementDecimal}`;
    }

    return Number(value).toFixed(2);
  }

  return {
    formatCurrencyInput,
  };
}
