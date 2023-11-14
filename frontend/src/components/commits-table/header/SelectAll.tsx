import * as React from 'react';
import { observer } from 'mobx-react';

interface SelectAllProps {
  isSelected: boolean;
  selectableCommitsCount: number;
  enableActions: boolean;
  onChange(isChecked: boolean): void;
}

const SelectAll: React.FunctionComponent<SelectAllProps> = (props) => {
  const {
    isSelected,
    selectableCommitsCount,
    enableActions,
    onChange,
  } = props;

  if (selectableCommitsCount === 0) {
    return <th className='column-cb' />;
  }

  return (
    <th className='column-cb manage-column check-column'>
      <label
        className='screen-reader-text'
        htmlFor='CommitsTable-selectAll'
      >
        Select All
      </label>
      <input
        type='checkbox'
        id='CommitsTable-selectAll'
        disabled={!enableActions}
        checked={isSelected}
        onChange={() => onChange(!isSelected)}
      />
    </th>
  );
};

export default observer(SelectAll);
