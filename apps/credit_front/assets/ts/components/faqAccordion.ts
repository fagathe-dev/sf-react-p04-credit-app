/**
 * Questions fréquentes (<details> dans .faq) : une seule réponse ouverte à la fois.
 * Sans JavaScript, les <details> restent utilisables nativement.
 */
export const initFaqAccordion = (root: ParentNode): void => {
  root.querySelectorAll<HTMLElement>('.faq').forEach((faq) => {
    const items = Array.from(faq.querySelectorAll<HTMLDetailsElement>('details'));

    items.forEach((item) => {
      item.addEventListener('toggle', () => {
        if (!item.open) return;
        items.filter((other) => other !== item).forEach((other) => {
          other.open = false;
        });
      });
    });
  });
};
