import * as React from 'react';
import { inject, observer } from 'mobx-react';

import Button from './Button';
import FlashMessage from './flash-message/FlashMessage';
import Panel from './panel/Panel';

import { ServicePanelStore } from '../../stores/servicePanelStore';

import './ServicePanel.less';

interface ServicePanelProps {
  children?: React.ReactNode;
  servicePanelStore?: ServicePanelStore;
}

class ServicePanel extends React.Component<ServicePanelProps, {}> {

  onButtonClick = () => {
    const { servicePanelStore } = this.props;
    servicePanelStore!.toggleVisibility();
  }

  render() {
    const { children, servicePanelStore } = this.props;
    const { message, isVisible } = servicePanelStore!;

    return (
      <div>
        <Button onClick={this.onButtonClick} />
        {children}
        {message &&
          <FlashMessage message={message} />
        }
        <Panel isVisible={isVisible} />
      </div>
    );
  }

}

export default inject('servicePanelStore')(observer(ServicePanel));
