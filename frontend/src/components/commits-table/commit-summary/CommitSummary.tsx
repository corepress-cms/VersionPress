/// <reference path='../../common/Commits.d.ts' />

import * as React from 'react';
import classNames from 'classnames';
import { observer } from 'mobx-react';

import Actions from './Actions';
import Author from './Author';
import Checkbox from './Checkbox';
import Date from './Date';
import Environment from '../environment/Environment';
import Message from './Message';
import UndoDisabledDialog from '../../dialogs/UndoDisabledDialog';
import UndoMergeDialog from '../../dialogs/UndoMergeDialog';
import DetailsLevel from '../../../enums/DetailsLevel';
import * as portal from '../../portal/portal';

interface CommitSummaryProps {
  commit: Commit;
  enableActions: boolean;
  isSelected: boolean;
  detailsLevel: DetailsLevel;
  showVisualisation: boolean;
  visualisation: Visualisation;
  onUndo(hash: string, message: string): void;
  onRollback(hash: string, date: string): void;
  onCommitsSelect(commits: Commit[], isChecked: boolean, isShiftKey: boolean): void;
  onDetailsLevelChange(detailsLevel: DetailsLevel): void;
  onToggleShowVisualisation(): void;
}

class CommitSummary extends React.Component<CommitSummaryProps, {}> {

  onRowClick = (e: React.MouseEvent<HTMLTableRowElement>) => {
    e.preventDefault();

    const { commit, detailsLevel, onDetailsLevelChange } = this.props;

    if (commit.isEnabled) {
      onDetailsLevelChange(detailsLevel === DetailsLevel.None ? DetailsLevel.Overview : DetailsLevel.None);
    }
  }

  onCheckboxClick = (shiftKey: boolean) => {
    const { commit, isSelected, onCommitsSelect } = this.props;

    onCommitsSelect([commit], !isSelected, shiftKey);
  }

  onUndoClick = () => {
    const { commit, enableActions, onUndo } = this.props;

    if (commit.isMerge) {
      this.displayUndoMergeDialog();
      return;
    }

    if (enableActions) {
      onUndo(commit.hash, commit.message);
    } else {
      this.displayDisabledDialog();
    }
  }

  onRollbackClick = () => {
    const { commit, enableActions, onRollback } = this.props;

    if (enableActions) {
      onRollback(commit.hash, commit.date);
    } else {
      this.displayDisabledDialog();
    }
  }

  private displayUndoMergeDialog() {
    portal.alertDialog(
      'This is a merge commit',
      <UndoMergeDialog />
    );
  }

  private displayDisabledDialog() {
    portal.alertDialog(
      <span>Undo <em>{this.props.commit.message}</em>?</span>,
      <UndoDisabledDialog />
    );
  }

  render() {
    const {
      commit,
      enableActions,
      isSelected,
      detailsLevel,
      showVisualisation,
      visualisation,
      onDetailsLevelChange,
      onToggleShowVisualisation,
    } = this.props;

    if (commit === null) {
      return null;
    }

    const rowClassName = classNames({
      'disabled': !commit.isEnabled,
      'displayed-details': detailsLevel !== DetailsLevel.None,
    });

    return (
      <tr className={rowClassName} onClick={this.onRowClick}>
        <Environment
          environment={commit.environment}
          showVisualisation={showVisualisation}
          visualisation={visualisation}
          onToggleShowVisualisation={onToggleShowVisualisation}
        />
        <Checkbox
          isVisible={commit.canUndo}
          isChecked={isSelected}
          isDisabled={!enableActions}
          onClick={this.onCheckboxClick}
        />
        <Date date={commit.date} />
        <Author author={commit.author} />
        <Message
          commit={commit}
          detailsLevel={detailsLevel}
          onDetailsLevelChange={onDetailsLevelChange}
        />
        <Actions
          commit={commit}
          enableActions={enableActions}
          onUndoClick={this.onUndoClick}
          onRollbackClick={this.onRollbackClick}
        />
      </tr>
    );
  }

}

export default observer(CommitSummary);
