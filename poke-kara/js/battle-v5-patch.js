(() => {
  const selector = '.DivMap#battleMap .Battle';
  const patchOne = (battle) => {
    if (!battle) return;

    // 1) Scope class
    battle.classList.add('pkx-battle-v5');

    // 2) Remove inline display:flex so CSS grid can take over
    if (battle.style) battle.style.removeProperty('display');

    // Helpers for direct children (avoid :scope for broader browser support)
    const children = Array.from(battle.children);
    const pokemonA = children.find(el => el.classList && el.classList.contains('PokemonA'));

    // 3) Ensure BattleActions exists right after PokemonA
    let actions = children.find(el => el.classList && el.classList.contains('BattleActions'));
    if (!actions) {
      actions = document.createElement('div');
      actions.className = 'BattleActions';
      if (pokemonA && pokemonA.nextSibling) battle.insertBefore(actions, pokemonA.nextSibling);
      else battle.appendChild(actions);
    } else if (actions.parentElement !== battle) {
      battle.appendChild(actions);
    }

    // 4) Move MoveBox under BattleActions
    if (pokemonA) {
      const moveBox = Array.from(pokemonA.children).find(el => el.classList && el.classList.contains('MoveBox'));
      if (moveBox && moveBox.parentElement !== actions) {
        if (moveBox.style) moveBox.style.removeProperty('display');
        actions.appendChild(moveBox);
      }
    }
  };

  const run = () => document.querySelectorAll(selector).forEach(patchOne);

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }
})();
