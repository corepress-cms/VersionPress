import * as React from 'react';
import { observer } from 'mobx-react';

import Rollback from './Rollback';
import Undo from './Undo';

interface ActionsProps {
  commit: Commit;
  enableActions: boolean;
  onUndoClick(): void;
  onRollbackClick(): void;
}

const Actions: React.FunctionComponent<ActionsProps> = (props) => {
  const {
    commit,
    enableActions,
    onUndoClick,
    onRollbackClick,
  } = props;

  if (!commit.isEnabled) {
    return <td className='column-actions' />;
  }

  return (
    <td className='column-actions'>
      {(commit.canUndo || commit.isMerge) &&
        <Undo
          commit={commit}
          enableActions={enableActions}
          onClick={onUndoClick}
        />
      }
      {commit.canRollback &&
        <Rollback
          enableActions={enableActions}
          onClick={onRollbackClick}
        />
      }
    </td>

  );
};

export default observer(Actions);
