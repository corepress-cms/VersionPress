import * as React from 'react';
import classNames from 'classnames';
import { observer } from 'mobx-react';

interface RollbackProps {
  enableActions: boolean;
  onClick(): void;
}

const Rollback: React.FunctionComponent<RollbackProps> = ({ enableActions, onClick }) => {
  const rollbackClassName = classNames({
    'vp-table-rollback': true,
    'disabled': !enableActions,
  });

  const title = !enableActions
    ? 'You have uncommitted changes in your WordPress directory.'
    : '';

  return (
    <a
      className={rollbackClassName}
      href='#'
      onClick={e => { e.stopPropagation(); e.preventDefault(); onClick(); }}
      title={title}
    >
      Roll back to this
    </a>
  );
};

export default observer(Rollback);
